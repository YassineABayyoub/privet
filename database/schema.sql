CREATE DATABASE IF NOT EXISTS archive_contrats
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE archive_contrats;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  display_name VARCHAR(160) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('judge', 'clerk') NOT NULL DEFAULT 'clerk',
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contracts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  contract_number VARCHAR(80) NOT NULL,
  contract_date DATE NOT NULL,
  registered_date DATE NULL,
  category VARCHAR(80) NOT NULL,
  act_type VARCHAR(120) NOT NULL,
  archive_number VARCHAR(100) NULL,
  reference_text VARCHAR(255) NULL,
  notes TEXT NULL,
  specific_data JSON NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_contracts_number (contract_number),
  UNIQUE KEY uq_contracts_archive_number (archive_number),
  KEY idx_contracts_category_type (category, act_type),
  KEY idx_contracts_date (contract_date),
  CONSTRAINT fk_contracts_creator
    FOREIGN KEY (created_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS persons (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(120) NOT NULL,
  last_name VARCHAR(160) NOT NULL,
  identity_number VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_persons_identity_number (identity_number),
  KEY idx_persons_name (last_name, first_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contract_parties (
  contract_id BIGINT UNSIGNED NOT NULL,
  person_id BIGINT UNSIGNED NOT NULL,
  party_role VARCHAR(100) NOT NULL,
  PRIMARY KEY (contract_id, person_id, party_role),
  KEY idx_contract_parties_person (person_id),
  CONSTRAINT fk_parties_contract
    FOREIGN KEY (contract_id) REFERENCES contracts (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_parties_person
    FOREIGN KEY (person_id) REFERENCES persons (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contract_documents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  contract_id BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(80) NOT NULL,
  mime_type VARCHAR(80) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL,
  uploaded_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_contract_documents_stored_name (stored_name),
  KEY idx_contract_documents_contract (contract_id),
  CONSTRAINT fk_documents_contract
    FOREIGN KEY (contract_id) REFERENCES contracts (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_documents_uploader
    FOREIGN KEY (uploaded_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
