-- Delivery Type dropdown (PO, Gitcall, DI, Kanban, Other - manual text when Other)
-- and Delivery Date (only used when delivery_type != PO), added next to Customer
-- on the Sales Order form.
-- Jalankan langsung di DB, bukan lewat migrasi.

ALTER TABLE public.sales_order_hdr
    ADD COLUMN IF NOT EXISTS delivery_type varchar(40) NULL,
    ADD COLUMN IF NOT EXISTS delivery_date date NULL;
