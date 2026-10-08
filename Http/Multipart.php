<?php

namespace Omnisign\Http;

/**
 * A multipart/form-data body, built here: no MIME library needed for a
 * file and a few fields.
 */
final class Multipart
{
    /**
     * @param array<string, string>                                            $fields
     * @param array<string, array{content: string, filename: string, type: string}> $files
     *
     * @return array{body: string, contentType: string}
     */
    public static function build(array $fields, array $files): array
    {
        $boundary = 'omnisign-'.bin2hex(random_bytes(12));
        $body = '';
        foreach ($fields as $name => $value) {
            $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"".addcslashes($name, "\"\\")."\"\r\n\r\n".$value."\r\n";
        }
        foreach ($files as $name => $file) {
            $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"".addcslashes($name, "\"\\").'"; filename="'.addcslashes($file['filename'], "\"\\")."\"\r\nContent-Type: ".$file['type']."\r\n\r\n".$file['content']."\r\n";
        }

        return ['body' => $body."--$boundary--\r\n", 'contentType' => 'multipart/form-data; boundary='.$boundary];
    }
}
