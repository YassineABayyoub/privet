USE archive_contrats;

CREATE TABLE IF NOT EXISTS contract_documents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  contract_id BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name CHAR(36) NOT NULL,
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
