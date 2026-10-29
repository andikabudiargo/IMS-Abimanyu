-- warehouse_movement.movement_code dulu diisi app-level lewat max(movement_code)+1,
-- tanpa lock -- dua posting dari modul MANAPUN (Adjustment, Transfer, Receiving, dll,
-- 13 controller sama pola) yang jalan bersamaan bisa dapat movement_code yang sama,
-- karena kolomnya juga tidak punya unique constraint.
--
-- Sequence ini jadi sumber nomor yang atomik di level database, dibuat mulai dari
-- max(movement_code) yang ada sekarang + 1 supaya tidak tabrakan dengan data lama.
-- CATATAN: sequence ini baru menutup race-nya kalau kode PHP benar-benar memanggil
-- nextval() -- lihat StockAdjustmentController::nextMovementCode(). Controller lain
-- yang masih pakai max()+1 manual TIDAK otomatis ikut aman hanya karena sequence ini
-- ada; mereka perlu dimigrasikan juga baru race lintas-modul benar-benar tertutup.

CREATE SEQUENCE IF NOT EXISTS warehouse_movement_code_seq;

SELECT setval(
    'warehouse_movement_code_seq',
    COALESCE((SELECT MAX(movement_code) FROM warehouse_movement), 0) + 1,
    false
);

ALTER SEQUENCE warehouse_movement_code_seq OWNED BY warehouse_movement.movement_code;

-- Rollback:
-- ALTER SEQUENCE warehouse_movement_code_seq OWNED BY NONE;
-- DROP SEQUENCE IF EXISTS warehouse_movement_code_seq;
