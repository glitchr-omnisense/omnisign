<?php

namespace Omnisign\Tests;

use Omnisign\Exception\InvalidConfigException;
use Omnisign\Model\Capabilities;
use Omnisign\Model\Envelope;
use Omnisign\Model\Field;
use Omnisign\Model\FieldType;
use Omnisign\Model\Signer;
use Omnisign\Model\StatementLimit;
use Omnisign\Model\StatementMode;
use Omnisign\Registry;
use PHPUnit\Framework\TestCase;

/**
 * Since 1.2: a statement the signer types or acknowledges, each provider's
 * limit declared, asked beforehand, and a statement that does not fit
 * refused before any call.
 */
final class StatementTest extends TestCase
{
    public function testALimitIsInCharactersOrInBytes(): void
    {
        $characters = new StatementLimit(StatementMode::TYPED, 10);
        $bytes = new StatementLimit(StatementMode::TYPED, 10, StatementLimit::BYTES);

        self::assertSame([6, 12], [$characters->length('éééééé'), $bytes->length('éééééé')]);
        self::assertTrue($characters->fits('éééééé'));
        self::assertFalse($bytes->fits('éééééé'));
        self::assertTrue((new StatementLimit(StatementMode::ACKNOWLEDGED, null))->fits(str_repeat('x', 100000)), 'no limit stated');

        $capabilities = new Capabilities(statements: [$characters]);
        self::assertSame($characters, $capabilities->statement());
        self::assertNull($capabilities->statement(StatementMode::ACKNOWLEDGED));
        self::assertFalse($capabilities->fits('court', StatementMode::ACKNOWLEDGED), 'a mode not carried');
        self::assertSame([['mode' => 'typed', 'maxLength' => 10, 'unit' => 'characters', 'source' => '']], $capabilities->toArray()['statements']);
        self::assertSame([], (new Capabilities())->statements);
    }

    public function testAFieldCarriesItsStatementAndItsMode(): void
    {
        $typed = Field::forStatement('surety', 'deed', 'Bon pour caution', page: 2, x: 10, y: 20, label: 'Mention');
        $acknowledged = Field::forStatement('surety', 'deed', 'Reçu', StatementMode::ACKNOWLEDGED);

        self::assertSame([FieldType::TEXT, StatementMode::TYPED, 'Mention', 2], [$typed->type, $typed->mode(), $typed->label, $typed->page]);
        self::assertSame(StatementMode::ACKNOWLEDGED, $acknowledged->mode());
        self::assertSame(StatementMode::TYPED, (new Field('s', 'd', FieldType::TEXT, statement: 'x'))->mode());
        self::assertNull((new Field('s', 'd', FieldType::TEXT, maxLength: 600))->mode());
    }

    public function testAStatementTheProviderCannotCarryIsRefusedBeforeAnyCall(): void
    {
        $gateway = (new Registry([new StubFactory()], ['stub' => ['factory' => 'stub', 'options' => ['host' => 'sign.example']]]))->get('stub');
        $envelope = new Envelope('Caution', signers: [new Signer('surety', 'Camille Érable', 'camille@erable.example')], fields: [Field::forStatement('surety', 'deed', 'Bon pour caution')]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The "stub" gateway carries no typed statement');
        $gateway->create($envelope);
    }
}
