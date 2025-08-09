<?php

namespace Overseer\Test;

use Overseer\DB\DB;
use Overseer\DTO\SessionDatabaseDTO;
use Overseer\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Session::class)]
final class SessionTest extends TestCase {
    private DB|MockObject $dbMock;

    public function setUp(): void {
        $this->dbMock = $this->createMock(DB::class);
    }

    public function testLoadBySessionId(): void {
        $sessionId = 1;
        $dto = new SessionDatabaseDTO(
            id: $sessionId,
            name: 'Test Session',
            creator: '1',
            members: '1|2|3|',
            password: '',
            battlefieldPower: 10,
            atheneum: '',
            exchange: 2,
        );

        $this->dbMock->expects($this->once())
            ->method('fetchFirst')
            ->with(<<<'SQL'
                    SELECT * FROM Sessions s
                    WHERE s.ID = ?
                    LIMIT 1
                    SQL,
                [$sessionId],
                SessionDatabaseDTO::class,
            )
            ->willReturn($dto);

        $session = new Session($this->dbMock);
        $session->loadSession($sessionId);

        $this->assertNotNull($session);
        $this->assertEquals($sessionId, $session->id);
        $this->assertEquals($dto->name, $session->name);
        $this->assertEquals($dto->creator, $session->creator);
        $this->assertEquals($dto->password, $session->password);
        $this->assertEquals($dto->battlefieldPower, $session->battlefieldPower);
        $this->assertEquals($dto->exchange, $session->exchange);
        $this->assertEmpty($session->atheneum->items);
        $this->assertContains(1, $session->members);
        $this->assertContains(2, $session->members);
        $this->assertContains(3, $session->members);
    }
}
