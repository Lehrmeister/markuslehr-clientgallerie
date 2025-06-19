<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Repository;

/**
 * Base Repository Interface für CRUD-Operationen
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Repository
 * @author Markus Lehr
 * @since 1.0.0
 */
interface RepositoryInterface 
{
    /**
     * Findet eine Entität anhand der ID
     */
    public function findById(int $id): ?array;
    
    /**
     * Findet alle Entitäten
     */
    public function findAll(array $options = []): array;
    
    /**
     * Findet Entitäten anhand von Kriterien
     */
    public function findBy(array $criteria, array $options = []): array;
    
    /**
     * Findet eine Entität anhand von Kriterien
     */
    public function findOneBy(array $criteria): ?array;
    
    /**
     * Erstellt eine neue Entität
     */
    public function create(array $data): int;
    
    /**
     * Aktualisiert eine Entität
     */
    public function update(int $id, array $data): bool;
    
    /**
     * Löscht eine Entität
     */
    public function delete(int $id): bool;
    
    /**
     * Zählt Entitäten anhand von Kriterien
     */
    public function count(array $criteria = []): int;
    
    /**
     * Prüft ob eine Entität existiert
     */
    public function exists(int $id): bool;
}
