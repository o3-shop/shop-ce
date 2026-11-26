-- decode-mysql8.sql
--
-- Decodes the two OXID-encrypted columns that the o3-shop migrations
-- Version20230322213324 (oxconfig.OXVARVALUE) and Version20230322214524
-- (oxuserpayments.OXVALUE) would normally decode — but which they skipIf() on
-- MySQL 8, because MySQL 8.0 REMOVED the ENCODE()/DECODE() functions.
--
-- This replays those migrations' up() logic (identical SQL + key
-- Config::DEFAULT_CONFIG_KEY). It therefore MUST be run on an engine that still
-- has DECODE() — i.e. MariaDB / MySQL 5.7 — NOT on MySQL 8 itself. The E2E driver
-- runs it on a throwaway MariaDB sidecar, then loads the decoded result into the
-- MySQL 8 target, so the shop's decode migrations become harmless no-ops there.
--
-- Run ONCE on a fresh fixture load: this script is NOT itself re-runnable (a second run
-- fails on `ADD COLUMN ..._UNENC`, which already exists). What IS idempotent is the shop's
-- own decode migration afterwards — it guards on the column still being a BLOB, so once these
-- columns are TEXT the shop treats them as already decoded and skips.

-- Decoded OXID config values are byte strings (historically latin1), not
-- guaranteed valid UTF-8. Disable strict mode so copying them into the TEXT
-- column stores the raw bytes instead of erroring (ER_TRUNCATED_WRONG_VALUE) —
-- this matches the shop's own non-strict migration connection.
SET SESSION sql_mode = '';

-- oxconfig.OXVARVALUE
ALTER TABLE oxconfig ADD COLUMN `OXVARVALUE_UNENC` text;
UPDATE oxconfig SET `OXVARVALUE_UNENC` = DECODE(OXVARVALUE, 'fq45QS09_fqyx09239QQ') WHERE 1;
ALTER TABLE oxconfig MODIFY COLUMN `OXVARVALUE` text;
UPDATE oxconfig SET `OXVARVALUE` = `OXVARVALUE_UNENC` WHERE 1;
ALTER TABLE oxconfig DROP COLUMN `OXVARVALUE_UNENC`;

-- oxuserpayments.OXVALUE
ALTER TABLE oxuserpayments ADD COLUMN `OXVALUE_UNENC` text;
UPDATE oxuserpayments SET `OXVALUE_UNENC` = DECODE(OXVALUE, 'fq45QS09_fqyx09239QQ') WHERE 1;
ALTER TABLE oxuserpayments MODIFY COLUMN `OXVALUE` text;
UPDATE oxuserpayments SET `OXVALUE` = `OXVALUE_UNENC` WHERE 1;
ALTER TABLE oxuserpayments DROP COLUMN `OXVALUE_UNENC`;
