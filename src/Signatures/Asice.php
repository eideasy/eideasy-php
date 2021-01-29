<?php

namespace EidEasy\Signatures;

class Asice
{
    /**
     * @param string $containerFile existing asice container in binary form
     * @param string $xadesSignature new signature that will be added to the container
     * @return string Asice container in binary form with new signature added
     */
    public function addSignatureAsice(string $containerFile, string $xadesSignature): string
    {
        $tempZipFile = tempnam(sys_get_temp_dir(), 'signature');
        file_put_contents($tempZipFile, $containerFile);

        $signature = new \DOMDocument();
        $signature->loadXML($xadesSignature);
        $node = $signature->firstChild; // asic:XAdESSignatures
        $node = $node->firstChild; // ds:Signature

        $signatureId = $node->getAttribute('Id');

        $zip = new \ZipArchive();

        $zip->open($tempZipFile);
        $zip->addFromString("META-INF/signatures$signatureId.xml", $xadesSignature);
        $zip->close();

        return file_get_contents($tempZipFile);
    }

    /**
     * @param array $files of files (fileName, fileContent, mimeType) that need to be signed. fileContent is in binary form.
     * @return string Asice container in binary form
     */
    public function createAsiceContainer(array $files): string
    {
        $zip         = new \ZipArchive();
        $tempZipFile = tempnam(sys_get_temp_dir(), "eideasy");

        $zip->open($tempZipFile, \ZipArchive::CREATE);
        $zip->addFromString("mimetype", "application/vnd.etsi.asic-e+zip");
        $zip->addEmptyDir('META-INF');

        $manifestTemplate = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
  <manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.etsi.asic-e+zip"/>
</manifest:manifest>
XML;

        $manifest  = simplexml_load_string($manifestTemplate);
        $namespace = 'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0';

        foreach ($files as $fileData) {
            // Store file.
            $name        = $fileData['fileName'];
            $fileContent = $fileData['fileContent'];

            $zip->addFromString($name, $fileContent);

            // Add file metadata to container manifest.xml.
            $newFileEntry = $manifest->addChild('file-entry');
            $newFileEntry->addAttribute('manifest:full-path', $name, $namespace);
            $xmlMime = $fileData['mimeType'];
            $newFileEntry->addAttribute('manifest:media-type', $xmlMime, $namespace);
        }

        $zip->addFromString('META-INF/manifest.xml', $manifest->asXML());
        $zip->close();

        return file_get_contents($tempZipFile);
    }
}
