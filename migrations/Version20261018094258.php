<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261018094258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE key_management_data_keys (id BLOB NOT NULL, scope VARCHAR(191) NOT NULL, key_material BLOB NOT NULL, master_key_id VARCHAR(255) NOT NULL, client VARCHAR(64) NOT NULL, binding BLOB NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX key_management_data_keys_scope_idx ON key_management_data_keys (scope, id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__customer AS SELECT id, name, email, phone, birth_date FROM customer');
        $this->addSql('DROP TABLE customer');
        $this->addSql('CREATE TABLE customer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email BLOB NOT NULL, phone BLOB DEFAULT NULL, birth_date BLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO customer (id, name, email, phone, birth_date) SELECT id, name, email, phone, birth_date FROM __temp__customer');
        $this->addSql('DROP TABLE __temp__customer');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE key_management_data_keys');
        $this->addSql('CREATE TEMPORARY TABLE __temp__customer AS SELECT id, name, email, phone, birth_date FROM customer');
        $this->addSql('DROP TABLE customer');
        $this->addSql('CREATE TABLE customer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, birth_date DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO customer (id, name, email, phone, birth_date) SELECT id, name, email, phone, birth_date FROM __temp__customer');
        $this->addSql('DROP TABLE __temp__customer');
    }
}
