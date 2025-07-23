<?php

namespace Overseer\DB;

use Exception;
use mysqli;
use Overseer\Caster\Caster;

/**
 * The DB class is (or at least should be) the only way that we interact with the
 * database. The goal of this class is to allow for the PHP side of things to have
 * strongly-typed queries to the server. This should let us (somewhat) safely handle
 * communication with the database, and avoid possible issues like SQL injection.
 */
class DB {
    private static ?mysqli $dbConnection = null;

    public function __construct() {
        self::initializeConnection();
    }

    /**
     * Initializes the connection if there's no connection already set
     * 
     * Going to be called for every query to ensure that we do have an actual
     * connection to work with here.
     */
    private static function initializeConnection(): void {
        if (self::$dbConnection === null) {
            self::$dbConnection = mysqli_connect(
                hostname: $_ENV['DB_HOSTNAME'],
                username: $_ENV['DB_USERNAME'],
                password: $_ENV['DB_PASSWORD'],
                database: $_ENV['DB_DATABASE'],
            );
        }
    }

    /**
     * @template T
     * @param string $sqlQuery The SQL query as a MySQLi prepared query
     * @param string $expectedTypes Expected types of the values to be used in the query
     * @param list<mixed> $values Values to be used in the query
     * @param class-string<T> $returnClass Class of the object to be returned by this function
     * @return list<T>
     * 
     * @throws Exception Throws a generic exception whenever non-SELECT queries are ran
     */
    public function fetchAll(
        string $sqlQuery,
        array $values,
        string $returnClass,
    ): array {
        if (str_starts_with(strtolower($sqlQuery), 'select')) {
            throw new DBException('This function only accepts SELECT queries!');
        }

        $result = self::$dbConnection->execute_query($sqlQuery, $values);
        if ($result === false) {
            $error = self::$dbConnection->error;
            throw new DBException("Query failed to execute! ({$error})");
        }

        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = Caster::arrayToObject($row, $returnClass);
        }
        
        return $results;
    }

    /**
     * Fetches the first element in a SELECT query and casts it to the expected class
     * Remember to add a LIMIT 1 at the end of your query!
     * 
     * @template T
     * @param string $sqlQuery The SQL query as a MySQLi prepared query
     * @param string $expectedTypes Expected types of the values to be used in the query
     * @param list<mixed> $values Values to be used in the query
     * @param class-string<T> $returnClass Class of the object to be returned by this function
     * @return T|null
     * 
     * @throws Exception Throws a generic exception whenever non-SELECT queries are ran
     */
    public function fetchFirst(
        string $sqlQuery,
        array $values,
        string $returnClass,
    ): ?object {
        if (str_starts_with(strtolower($sqlQuery), 'select')) {
            throw new DBException('This function only accepts SELECT queries!');
        }

        $result = self::$dbConnection->execute_query($sqlQuery, $values);
        if ($result === false) {
            $error = self::$dbConnection->error;
            throw new DBException("Query failed to execute! ({$error})");
        }

        $row = $result->fetch_assoc();
        if ($row === false) {
            $error = self::$dbConnection->error;
            throw new DBException("Query failed to execute! ({$error})");
        }
        if ($row === null) {
            // Query set is empty
            return null;
        }
        
        return Caster::arrayToObject($row, $returnClass);
    }

    /**
     * Runs an INSERT INTO query and returns the first ID of the inserted objects (if multiple)
     * 
     * @param string $sqlQuery The SQL query as a MySQLi prepared query
     * @param string $expectedTypes Expected types of the values to be used in the query
     */
    public function insert(
        string $sqlQuery,
        array $values,
    ): int {
        if (str_starts_with(strtolower($sqlQuery), 'insert into')) {
            throw new DBException('This function only accepts INSERT INTO queries!');
        }

        $result = self::$dbConnection->execute_query($sqlQuery, $values);
        if ($result === false) {
            $error = self::$dbConnection->error;
            throw new DBException("Query failed to execute! ({$error})");
        }

        return (int)self::$dbConnection->insert_id;
    }

    /**
     * Runs a DELETE query and returns true if successful
     * 
     * @param string $sqlQuery The SQL query as a MySQLi prepared query
     * @param string $expectedTypes Expected types of the values to be used in the query
     */
    public function delete(
        string $sqlQuery,
        array $values,
    ): bool {
        if (str_starts_with(strtolower($sqlQuery), 'delete')) {
            throw new DBException('This function only accepts DELETE queries!');
        }

        $result = self::$dbConnection->execute_query($sqlQuery, $values);
        if ($result === false) {
            $error = self::$dbConnection->error;
            throw new DBException("Query failed to execute! ({$error})");
        }

        return (bool)$result;
    }
}
