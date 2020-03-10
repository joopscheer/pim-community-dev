<?php

declare(strict_types=1);

namespace Akeneo\Connectivity\Connection\Infrastructure\Persistence\Dbal\Query;

use Akeneo\Connectivity\Connection\Domain\Audit\Persistence\Query\SelectConnectionsEventCountByDayQuery;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

/**
 * @author Romain Monceau <romain@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class DbalSelectConnectionsEventCountByDayQuery implements SelectConnectionsEventCountByDayQuery
{
    /** @var Connection */
    private $dbalConnection;

    public function __construct(Connection $dbalConnection)
    {
        $this->dbalConnection = $dbalConnection;
    }

    /**
     * Return normalized data of hourly event counts per connection and the sum for all connections (<all>).
     *
     * Type:
     * {
     *   '<all>': Array<[DateTime, int]>,
     *   [connectionCode: string]: Array<[DateTime, int]>
     * }
     */
    public function execute(
        string $eventType,
        \DateTimeInterface $fromDateTime,
        \DateTimeInterface $upToDateTime
    ): array {
        $hourlyEventCountsPerConnection = $this->getHourlyEventCountsPerConnection(
            $eventType,
            $fromDateTime,
            $upToDateTime
        );

        $sumOfHourlyEventCountsForAllConnections = $this->getSumOfHourlyEventCountsForAllConnections(
            $eventType,
            $fromDateTime,
            $upToDateTime
        );

        return array_merge($hourlyEventCountsPerConnection, $sumOfHourlyEventCountsForAllConnections);
    }

    private function getSumOfHourlyEventCountsForAllConnections(
        string $eventType,
        \DateTimeInterface $fromDateTime,
        \DateTimeInterface $upToDateTime
    ): array {
        $sql = <<<SQL
SELECT connection_code, event_datetime, event_count
FROM akeneo_connectivity_connection_audit
WHERE connection_code = '<all>'
AND event_datetime >= :from_datetime AND event_datetime < :up_to_datetime
AND event_type = :event_type
ORDER BY event_datetime
SQL;
        $hourlyEventCountsData = $this->dbalConnection->executeQuery(
            $sql,
            [
                'from_datetime' => $fromDateTime,
                'up_to_datetime' => $upToDateTime,
                'event_type' => $eventType,
            ],
            [
                'from_datetime' => Types::DATETIME_IMMUTABLE,
                'up_to_datetime' => Types::DATETIME_IMMUTABLE,
            ]
        )->fetchAll();

        return $this->normalizeHourlyEventCountsData($hourlyEventCountsData);
    }

    private function getHourlyEventCountsPerConnection(
        string $eventType,
        \DateTimeInterface $fromDateTime,
        \DateTimeInterface $upToDateTime
    ): array {
        $sql = <<<SQL
SELECT conn.code as connection_code, audit.event_datetime, audit.event_count
FROM akeneo_connectivity_connection conn
LEFT JOIN akeneo_connectivity_connection_audit audit ON audit.connection_code = conn.code
AND audit.event_datetime >= :from_datetime AND audit.event_datetime < :up_to_datetime
AND audit.event_type = :event_type
ORDER BY conn.code, audit.event_datetime
SQL;

        $hourlyEventCountsData = $this->dbalConnection->executeQuery(
            $sql,
            [
                'from_datetime' => $fromDateTime,
                'up_to_datetime' => $upToDateTime,
                'event_type' => $eventType,
            ],
            [
                'from_datetime' => Types::DATETIME_IMMUTABLE,
                'up_to_datetime' => Types::DATETIME_IMMUTABLE,
            ]
        )->fetchAll();

        return $this->normalizeHourlyEventCountsData($hourlyEventCountsData);
    }

    /**
     * Return normalized data.
     *
     * Type:
     * { [connectionCode: string]: Array<[DateTime, int]> }
     *
     * Example:
     * [
     *   'erp' => [
     *     [DateTime(2020-01-01 00:00:00), 1],
     *     [DateTime(2020-01-01 01:00:00), 3],
     *     ...
     *     [DateTime(2020-01-09 00:00:00), 7]
     *   ],
     *   ...
     * ]
     */
    private function normalizeHourlyEventCountsData(array $hourlyEventCountsData): array
    {
        $format = $this->dbalConnection->getDatabasePlatform()->getDateTimeFormatString();

        return array_reduce(
            $hourlyEventCountsData,
            function (array $data, array $row) use ($format) {
                $connectionCode = $row['connection_code'];

                if (false === isset($data[$connectionCode])) {
                    $data[$connectionCode] = [];
                }

                if (null !== $row['event_datetime'] && null !== $row['event_count']) {
                    $data[$connectionCode][] = [
                        \DateTimeImmutable::createFromFormat(
                            $format,
                            $row['event_datetime'],
                            new \DateTimeZone('UTC')
                        ),
                        $row['event_count']
                    ];
                }

                return $data;
            },
            []
        );
    }
}
