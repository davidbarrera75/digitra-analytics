<?php

  namespace App\Console\Commands;

  use Illuminate\Console\Command;
  use Illuminate\Support\Facades\DB;
  use Carbon\Carbon;

  class DigitraSync extends Command
  {
      protected $signature = "digitra:sync {--entity=all} {--full}";
      protected $description = "Sincronizar datos de Digitra a base de datos local";

      public function handle()
      {
          $entity = $this->option("entity");
          $full = $this->option("full");

          $this->info("🚀 Iniciando sincronización de Digitra...");
          $this->newLine();

          if ($full) {
              $this->warn("⚠️  SINCRONIZACIÓN COMPLETA - Esto puede tomar varios minutos");
              $this->newLine();
          }

          $startTime = now();

          try {
              if ($entity === "all" || $entity === "users") {
                  $this->syncUsers($full);
              }

              if ($entity === "all" || $entity === "establecimientos") {
                  $this->syncEstablecimientos($full);
              }

              if ($entity === "all" || $entity === "reservas") {
                  $this->syncReservas($full);
              }

              if ($entity === "all" || $entity === "huespedes") {
                  $this->syncHuespedes($full);
              }

              $duration = now()->diffInSeconds($startTime);

              $this->newLine();
              $this->info("✅ Sincronización completada en {$duration} segundos");

              return Command::SUCCESS;

          } catch (\Exception $e) {
              $this->error("❌ Error: " . $e->getMessage());
              return Command::FAILURE;
          }
      }

      protected function syncUsers($full = false)
      {
          $this->info("👤 Sincronizando Usuarios...");

          $logId = DB::table("digitra_sync_log")->insertGetId([
              "entity" => "users",
              "started_at" => now(),
              "status" => "running",
              "created_at" => now(),
              "updated_at" => now(),
          ]);

          try {
              // Get users from Digitra
              $digitraUsers = DB::connection("digitra")
                  ->table("users")
                  ->select("id", "name", "email", "created_at", "updated_at")
                  ->orderBy("id")
                  ->get();

              $total = $digitraUsers->count();
              $this->line("  Total a sincronizar: {$total}");

              if ($total === 0) {
                  DB::table("digitra_sync_log")->where("id", $logId)->update([
                      "completed_at" => now(),
                      "status" => "completed",
                      "records_synced" => 0,
                      "duration_seconds" => now()->diffInSeconds(DB::table("digitra_sync_log")->where("id", $logId)->value("started_at")),
                  ]);
                  $this->line("  ✓ Sin cambios");
                  return;
              }

              $bar = $this->output->createProgressBar($total);
              $bar->start();

              $created = 0;
              $updated = 0;

              foreach ($digitraUsers as $digitraUser) {
                  // Check if tenant already exists
                  $tenant = DB::table("tenants")
                      ->where("digitra_user_id", $digitraUser->id)
                      ->first();

                  // Generate unique slug
                  $baseSlug = \Illuminate\Support\Str::slug($digitraUser->name);
                  $slug = $baseSlug;
                  $counter = 1;

                  while (DB::table("tenants")->where("slug", $slug)->where("digitra_user_id", "!=", $digitraUser->id)->exists()) {
                      $slug = $baseSlug . "-" . $counter;
                      $counter++;
                  }

                  $tenantData = [
                      "name" => $digitraUser->name,
                      "slug" => $slug,
                      "digitra_user_id" => $digitraUser->id,
                      "email" => $digitraUser->email,
                      "is_active" => 1,
                      "updated_at" => now(),
                  ];


                  if ($tenant) {
                      // Update existing tenant
                      DB::table("tenants")
                          ->where("id", $tenant->id)
                          ->update($tenantData);
                      $tenantId = $tenant->id;
                      $updated++;
                  } else {
                      // Create new tenant
                      $tenantData["created_at"] = now();
                      $tenantData["trial_ends_at"] = now()->addDays(30);
                      $tenantId = DB::table("tenants")->insertGetId($tenantData);
                      $created++;
                  }

                  // Check if user already exists
                  $user = DB::table("users")
                      ->where("email", $digitraUser->email)
                      ->first();

                  $userData = [
                      "name" => $digitraUser->name,
                      "email" => $digitraUser->email,
                      "tenant_id" => $tenantId,
                      "updated_at" => now(),
                  ];

                  if (!$user) {
                      // Create new user with a random password
                      $userData["password"] = bcrypt(\Illuminate\Support\Str::random(16));
                      $userData["created_at"] = now();
                      DB::table("users")->insert($userData);
                  } else {
                      // Update existing user
                      DB::table("users")
                          ->where("id", $user->id)
                          ->update($userData);
                  }

                  $bar->advance();
              }

              $bar->finish();
              $this->newLine();

              DB::table("digitra_sync_log")->where("id", $logId)->update([
                  "completed_at" => now(),
                  "status" => "completed",
                  "records_synced" => $total,
                  "records_created" => $created,
                  "records_updated" => $updated,
                  "duration_seconds" => now()->diffInSeconds(DB::table("digitra_sync_log")->where("id", $logId)->value("started_at")),
              ]);

              $this->line("  ✓ Creados: {$created} | Actualizados: {$updated}");

          } catch (\Exception $e) {
              DB::table("digitra_sync_log")->where("id", $logId)->update([
                  "completed_at" => now(),
                  "status" => "failed",
                  "error_message" => $e->getMessage(),
              ]);
              throw $e;
          }
      }

      protected function syncEstablecimientos($full = false)
      {
          $this->info("📍 Sincronizando Establecimientos...");

          $logId = DB::table("digitra_sync_log")->insertGetId([
              "entity" => "establecimientos",
              "started_at" => now(),
              "status" => "running",
              "created_at" => now(),
              "updated_at" => now(),
          ]);

          try {
              $query = DB::connection("digitra")->table("establecimientos")
                  ->select("id", "nombre", "direccion", "codigo_dane", "nit", "email", "rnt",
                           "uuid", "user_id", "indicativo", "telefono", "deleted", "created_at", "updated_at")
                  ->where("deleted", 0)
                  ->orderBy("id");

              if (!$full) {
                  $lastSync = DB::table("digitra_establecimientos")->max("synced_at");
                  if ($lastSync) {
                      $query->where("updated_at", ">", $lastSync);
                  }
              }

              $total = $query->count();
              $this->line("  Total a sincronizar: {$total}");

              if ($total === 0) {
                  DB::table("digitra_sync_log")->where("id", $logId)->update([
                      "completed_at" => now(),
                      "status" => "completed",
                      "records_synced" => 0,
                      "duration_seconds" => now()->diffInSeconds(DB::table("digitra_sync_log")->where("id", $logId)->value("started_at")),
                  ]);
                  $this->line("  ✓ Sin cambios");
                  return;
              }

              $bar = $this->output->createProgressBar($total);
              $bar->start();

              $created = 0;
              $updated = 0;

              $query->chunk(500, function ($establecimientos) use (&$created, &$updated, $bar) {
                  $rows = [];
                  foreach ($establecimientos as $est) {
                      $rows[] = [
                          "digitra_id" => $est->id,
                          "nombre" => $est->nombre,
                          "direccion" => $est->direccion,
                          "codigo_dane" => $est->codigo_dane,
                          "nit" => $est->nit,
                          "email" => $est->email,
                          "rnt" => $est->rnt,
                          "uuid" => $est->uuid,
                          "user_id" => $est->user_id,
                          "indicativo" => $est->indicativo,
                          "telefono" => $est->telefono,
                          "deleted" => $est->deleted,
                          "digitra_created_at" => $est->created_at,
                          "digitra_updated_at" => $est->updated_at,
                          "synced_at" => now(),
                          "updated_at" => now(),
                          "created_at" => now(),
                      ];
                      $bar->advance();
                  }

                  // Bulk upsert: insert or update on digitra_id conflict
                  $result = DB::table("digitra_establecimientos")->upsert(
                      $rows,
                      ["digitra_id"], // unique key
                      ["nombre", "direccion", "codigo_dane", "nit", "email", "rnt", "uuid",
                       "user_id", "indicativo", "telefono", "deleted", "digitra_created_at",
                       "digitra_updated_at", "synced_at", "updated_at"]
                  );
                  $updated += count($rows); // upsert doesn't distinguish, count total
              });

              $bar->finish();
              $this->newLine();

              DB::table("digitra_sync_log")->where("id", $logId)->update([
                  "completed_at" => now(),
                  "status" => "completed",
                  "records_synced" => $total,
                  "records_created" => $created,
                  "records_updated" => $updated,
                  "duration_seconds" => now()->diffInSeconds(DB::table("digitra_sync_log")->where("id", $logId)->value("started_at")),
              ]);

              $this->line("  ✓ Creados: {$created} | Actualizados: {$updated}");

          } catch (\Exception $e) {
              DB::table("digitra_sync_log")->where("id", $logId)->update([
                  "completed_at" => now(),
                  "status" => "failed",
                  "error_message" => $e->getMessage(),
              ]);
              throw $e;
          }
      }

      protected function syncReservas($full = false)
      {
          $this->info("📅 Sincronizando Reservas...");

          $logId = DB::table("digitra_sync_log")->insertGetId([
              "entity" => "reservas",
              "started_at" => now(),
              "status" => "running",
              "created_at" => now(),
              "updated_at" => now(),
          ]);

          try {
              $query = DB::connection("digitra")->table("reservas")
                  ->select("id", "establecimiento_id", "check_in", "check_out", "numero_acompanantes",
                           "precio", "motivo", "created_at", "updated_at")
                  ->orderBy("id");

              if (!$full) {
                  $lastSync = DB::table("digitra_reservas")->max("synced_at");
                  if ($lastSync) {
                      $query->where("updated_at", ">", $lastSync);
                  }
              }

              $total = $query->count();
              $this->line("  Total a sincronizar: {$total}");

              if ($total === 0) {
                  DB::table("digitra_sync_log")->where("id", $logId)->update([
                      "completed_at" => now(),
                      "status" => "completed",
                      "records_synced" => 0,
                      "duration_seconds" => now()->diffInSeconds(DB::table("digitra_sync_log")->where("id", $logId)->value("started_at")),
                  ]);
                  $this->line("  ✓ Sin cambios");
                  return;
              }

              $bar = $this->output->createProgressBar($total);
              $bar->start();

              $created = 0;
              $updated = 0;

              $query->chunk(1000, function ($reservas) use (&$created, &$updated, $bar) {
                  $rows = [];
                  foreach ($reservas as $res) {
                      $rows[] = [
                          "digitra_id" => $res->id,
                          "establecimiento_id" => $res->establecimiento_id,
                          "establecimiento_digitra_id" => $res->establecimiento_id,
                          "check_in" => $res->check_in,
                          "check_out" => $res->check_out,
                          "num_adultos" => $res->numero_acompanantes ?? 0,
                          "num_ninos" => 0,
                          "total_pagado" => $res->precio,
                          "estado" => null,
                          "canal_reserva" => null,
                          "observaciones" => $res->motivo,
                          "digitra_created_at" => $res->created_at,
                          "digitra_updated_at" => $res->updated_at,
                          "synced_at" => now(),
                          "updated_at" => now(),
                          "created_at" => now(),
                      ];
                      $bar->advance();
                  }

                  DB::table("digitra_reservas")->upsert(
                      $rows,
                      ["digitra_id"],
                      ["establecimiento_id", "establecimiento_digitra_id", "check_in", "check_out",
                       "num_adultos", "num_ninos", "total_pagado", "estado", "canal_reserva",
                       "observaciones", "digitra_created_at", "digitra_updated_at", "synced_at", "updated_at"]
                  );
                  $updated += count($rows);
              });

              $bar->finish();
              $this->newLine();

              DB::table("digitra_sync_log")->where("id", $logId)->update([
                  "completed_at" => now(),
                  "status" => "completed",
                  "records_synced" => $total,
                  "records_created" => $created,
                  "records_updated" => $updated,
                  "duration_seconds" => now()->diffInSeconds(DB::table("digitra_sync_log")->where("id", $logId)->value("started_at")),
              ]);

              $this->line("  ✓ Creados: {$created} | Actualizados: {$updated}");

          } catch (\Exception $e) {
              DB::table("digitra_sync_log")->where("id", $logId)->update([
                  "completed_at" => now(),
                  "status" => "failed",
                  "error_message" => $e->getMessage(),
              ]);
              throw $e;
          }
      }

      protected function syncHuespedes($full = false)
      {
          $this->info("👥 Sincronizando Huéspedes...");

          $logId = DB::table("digitra_sync_log")->insertGetId([
              "entity" => "huespedes",
              "started_at" => now(),
              "status" => "running",
              "created_at" => now(),
              "updated_at" => now(),
          ]);

          try {
              $query = DB::connection("digitra")->table("huespedes")
                  ->select("id", "reserva_id", "tipo_documento", "numero_documento", "nombres",
                           "apellidos", "nacionalidad", "fecha_nacimiento", "principal")
                  ->orderBy("id");

              if (!$full) {
                  $lastSync = DB::table("digitra_huespedes")->max("synced_at");
                  if ($lastSync) {
                      $query->where("updated_at", ">", $lastSync);
                  }
              }

              $total = $query->count();
              $this->line("  Total a sincronizar: {$total}");

              if ($total === 0) {
                  DB::table("digitra_sync_log")->where("id", $logId)->update([
                      "completed_at" => now(),
                      "status" => "completed",
                      "records_synced" => 0,
                      "duration_seconds" => now()->diffInSeconds(DB::table("digitra_sync_log")->where("id", $logId)->value("started_at")),
                  ]);
                  $this->line("  ✓ Sin cambios");
                  return;
              }

              $bar = $this->output->createProgressBar($total);
              $bar->start();

              $created = 0;
              $updated = 0;

              $query->chunk(1000, function ($huespedes) use (&$created, &$updated, $bar) {
                  $rows = [];
                  foreach ($huespedes as $huesped) {
                      $rows[] = [
                          "digitra_id" => $huesped->id,
                          "reserva_id" => $huesped->reserva_id,
                          "reserva_digitra_id" => $huesped->reserva_id,
                          "tipo_documento" => $huesped->tipo_documento,
                          "numero_documento" => $huesped->numero_documento,
                          "nombre" => $huesped->nombres,
                          "apellido" => $huesped->apellidos,
                          "email" => null,
                          "telefono" => null,
                          "nacionalidad" => $huesped->nacionalidad,
                          "fecha_nacimiento" => $huesped->fecha_nacimiento,
                          "genero" => null,
                          "synced_at" => now(),
                          "updated_at" => now(),
                          "created_at" => now(),
                      ];
                      $bar->advance();
                  }

                  DB::table("digitra_huespedes")->upsert(
                      $rows,
                      ["digitra_id"],
                      ["reserva_id", "reserva_digitra_id", "tipo_documento", "numero_documento",
                       "nombre", "apellido", "email", "telefono", "nacionalidad",
                       "fecha_nacimiento", "genero", "synced_at", "updated_at"]
                  );
                  $updated += count($rows);
              });

              $bar->finish();
              $this->newLine();

              DB::table("digitra_sync_log")->where("id", $logId)->update([
                  "completed_at" => now(),
                  "status" => "completed",
                  "records_synced" => $total,
                  "records_created" => $created,
                  "records_updated" => $updated,
                  "duration_seconds" => now()->diffInSeconds(DB::table("digitra_sync_log")->where("id", $logId)->value("started_at")),
              ]);

              $this->line("  ✓ Creados: {$created} | Actualizados: {$updated}");

          } catch (\Exception $e) {
              DB::table("digitra_sync_log")->where("id", $logId)->update([
                  "completed_at" => now(),
                  "status" => "failed",
                  "error_message" => $e->getMessage(),
              ]);
              throw $e;
          }
      }
  }
