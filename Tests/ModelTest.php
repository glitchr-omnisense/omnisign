<?php

namespace Omnisign\Tests;

use Omnisign\Exception\InvalidConfigException;
use Omnisign\Http\Multipart;
use Omnisign\Model\Capabilities;
use Omnisign\Model\Envelope;
use Omnisign\Model\Field;
use Omnisign\Model\File;
use Omnisign\Model\Level;
use Omnisign\Model\Signer;
use Omnisign\Model\Status;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testASignersNamesAndAnEnvelopesParts(): void
    {
        self::assertSame(['Camille Anne', 'Érable'], (new Signer('t', 'Camille Anne Érable', 'c@example.org'))->names());
        self::assertSame(['Madonna', 'Madonna'], (new Signer('t', 'Madonna', 'm@example.org'))->names());

        $envelope = new Envelope('Bail', signers: [new Signer('landlord', 'Marco Meyer', 'm@example.org'), new Signer('tenant', 'Camille Érable', 'c@example.org')], fields: [new Field('tenant', 'lease'), new Field('landlord', 'lease'), new Field('tenant', 'annex')]);
        self::assertCount(2, $envelope->fieldsOf('tenant'));
        self::assertSame('Camille Érable', $envelope->signer('tenant')->name);
        self::assertNull($envelope->state('tenant'));
        $sent = $envelope->with(reference: 'r1', status: Status::SENT);
        self::assertSame(['r1', Status::SENT, 'Bail', null], [$sent->reference(), $sent->status, $sent->title, $envelope->reference]);

        $this->expectException(InvalidConfigException::class);
        $envelope->signer('notary');
    }

    public function testStatusesFilesCapabilitiesAndAMultipartBody(): void
    {
        self::assertTrue(Status::COMPLETED->isOver());
        self::assertFalse(Status::SENT->isOver());
        self::assertSame(['simple', 'qualified'], (new Capabilities([Level::SIMPLE, Level::QUALIFIED]))->toArray()['levels']);
        self::assertFalse((new Capabilities())->offers(Level::ADVANCED));
        self::assertSame(hash('sha256', '%PDF'), (new File('%PDF', 'a.pdf'))->sha256());
        self::assertSame(['ModelTest.php', 'application/octet-stream'], [File::fromPath(__FILE__)->filename, File::fromPath(__FILE__)->mimeType]);

        $multipart = Multipart::build(['nature' => 'signable_document'], ['file' => ['content' => '%PDF', 'filename' => 'bail "v2".pdf', 'type' => 'application/pdf']]);
        self::assertMatchesRegularExpression('~^multipart/form-data; boundary=(omnisign-[0-9a-f]{24})$~', $multipart['contentType']);
        $boundary = substr($multipart['contentType'], strlen('multipart/form-data; boundary='));
        self::assertSame("--$boundary\r\nContent-Disposition: form-data; name=\"nature\"\r\n\r\nsignable_document\r\n--$boundary\r\nContent-Disposition: form-data; name=\"file\"; filename=\"bail \\\"v2\\\".pdf\"\r\nContent-Type: application/pdf\r\n\r\n%PDF\r\n--$boundary--\r\n", $multipart['body']);
    }
}
