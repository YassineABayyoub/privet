ALTER TABLE contracts
  ADD COLUMN contract_number_fr VARCHAR(80) NULL AFTER contract_number,
  ADD COLUMN archive_number_fr VARCHAR(100) NULL AFTER archive_number,
  ADD COLUMN reference_text_fr VARCHAR(255) NULL AFTER reference_text,
  ADD COLUMN notes_fr TEXT NULL AFTER notes,
  ADD COLUMN specific_data_fr JSON NULL AFTER specific_data,
  ADD UNIQUE KEY uq_contracts_number_fr (contract_number_fr),
  ADD UNIQUE KEY uq_contracts_archive_number_fr (archive_number_fr);

ALTER TABLE persons
  ADD COLUMN first_name_fr VARCHAR(120) NULL AFTER first_name,
  ADD COLUMN last_name_fr VARCHAR(160) NULL AFTER last_name;

ALTER TABLE contract_parties
  ADD COLUMN party_role_fr VARCHAR(100) NULL AFTER party_role;
