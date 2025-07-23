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

    /**
     * Initializes the connection if there's no connection already set
     * 
     * Going to be called for every query to ensure that we do have an actual
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
     * @param string $sqlQuery The SQL query as a sprintf-formatted string
     * @param string $expectedTypes Expected types of the values to be used in the query
     * @param list<mixed> $values Values to be used in the query
     * @param class-string<T> $returnClass Class of the object to be returned by this function
     * @return list<T>
     * 
     * @throws Exception Throws a generic exception whenever non-SELECT queries are ran
     */
    public static function fetchAll(
        string $sqlQuery,
        array $values,
        string $returnClass,
    ): array {
        self::initializeConnection();

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
     * @param string $sqlQuery The SQL query as a sprintf-formatted string
     * @param string $expectedTypes Expected types of the values to be used in the query
     * @param list<mixed> $values Values to be used in the query
     * @param class-string<T> $returnClass Class of the object to be returned by this function
     * @return T|null
     * 
     * @throws Exception Throws a generic exception whenever non-SELECT queries are ran
     */
    public static function fetchFirst(
        string $sqlQuery,
        array $values,
        string $returnClass,
    ): ?object {
        self::initializeConnection();

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

    // TODO: Implement INSERT and DELETE
}
