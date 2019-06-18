<?php

/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see         https://github.com/PHPOffice/PHPWord
 * @copyright   2010-2018 PHPWord contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord;

use PhpOffice\PhpWord\Escaper\RegExp;
use PhpOffice\PhpWord\Escaper\Xml;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\Shared\ZipArchive;
use PhpOffice\Common\Text;

class TemplateProcessor {

    const MAXIMUM_REPLACEMENTS_DEFAULT = -1;
     const SEARCH_LEFT = -1;
    const SEARCH_RIGHT = 1;
    const SEARCH_AROUND = 0;
    /**
     * Enable/disable setValue('key') becoming setValue('${key}') automatically.
     * Call it like: TemplateProcessor::$ensureMacroCompletion = false;
     *
     * @var bool
     */
    public static $ensureMacroCompletion = true;
    /**
     * ZipArchive object.
     *
     * @var mixed
     */
    protected $zipClass;

    /**
     * @var string Temporary document filename (with path)
     */
    protected $tempDocumentFilename;

    /**
     * Content of main document part (in XML format) of the temporary document
     *
     * @var string
     */
    protected $tempDocumentMainPart;

    /**
     * Content of headers (in XML format) of the temporary document
     *
     * @var string[]
     */
    protected $tempDocumentHeaders = array();

    /**
     * Content of footers (in XML format) of the temporary document
     *
     * @var string[]
     */
    protected $tempDocumentFooters = array();

    /**
     * Document relations (in XML format) of the temporary document.
     *
     * @var string[]
     */
    protected $tempDocumentRelations = array();

    /**
     * Document content types (in XML format) of the temporary document.
     *
     * @var string
     */
    protected $tempDocumentContentTypes = '';

    /**
     * new inserted images list
     *
     * @var string[]
     */
    protected $tempDocumentNewImages = array();

    /**
     * @since 0.12.0 Throws CreateTemporaryFileException and CopyFileException instead of Exception
     *
     * @param string $documentTemplate The fully qualified template filename
     *
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     */
    public function __construct($documentTemplate) {
        // Temporary document filename initialization
        $this->tempDocumentFilename = tempnam(Settings::getTempDir(), 'PhpWord');
        if (false === $this->tempDocumentFilename) {
            throw new CreateTemporaryFileException(); // @codeCoverageIgnore
        }

        // Template file cloning
        if (false === copy($documentTemplate, $this->tempDocumentFilename)) {
            throw new CopyFileException($documentTemplate, $this->tempDocumentFilename); // @codeCoverageIgnore
        }

        // Temporary document content extraction
        $this->zipClass = new ZipArchive();
        $this->zipClass->open($this->tempDocumentFilename);
        $index = 1;
        while (false !== $this->zipClass->locateName($this->getHeaderName($index))) {
            $this->tempDocumentHeaders[$index] = $this->readPartWithRels($this->getHeaderName($index));
            $index++;
        }
        $index = 1;
        while (false !== $this->zipClass->locateName($this->getFooterName($index))) {
            $this->tempDocumentFooters[$index] = $this->readPartWithRels($this->getFooterName($index));
            $index++;
        }

        $this->tempDocumentMainPart = $this->readPartWithRels($this->getMainPartName());
        $this->tempDocumentContentTypes = $this->zipClass->getFromName($this->getDocumentContentTypesName());
    }

    /**
     * Expose zip class
     *
     * To replace an image: $templateProcessor->zip()->AddFromString("word/media/image1.jpg", file_get_contents($file));<br>
     * To read a file: $templateProcessor->zip()->getFromName("word/media/image1.jpg");
     *
     * @return \PhpOffice\PhpWord\Shared\ZipArchive
     */
    public function zip() {
        return $this->zipClass;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function readPartWithRels($fileName) {
        $relsFileName = $this->getRelationsName($fileName);
        $partRelations = $this->zipClass->getFromName($relsFileName);
        if ($partRelations !== false) {
            $this->tempDocumentRelations[$fileName] = $partRelations;
        }

        return $this->fixBrokenMacros($this->zipClass->getFromName($fileName));
    }

    /**
     * @param string $xml
     * @param \XSLTProcessor $xsltProcessor
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     *
     * @return string
     */
    protected function transformSingleXml($xml, $xsltProcessor) {
        libxml_disable_entity_loader(true);
        $domDocument = new \DOMDocument();
        if (false === $domDocument->loadXML($xml)) {
            throw new Exception('Could not load the given XML document.');
        }

        $transformedXml = $xsltProcessor->transformToXml($domDocument);
        if (false === $transformedXml) {
            throw new Exception('Could not transform the given XML document.');
        }

        return $transformedXml;
    }

    /**
     * @param mixed $xml
     * @param \XSLTProcessor $xsltProcessor
     *
     * @return mixed
     */
    protected function transformXml($xml, $xsltProcessor) {
        if (is_array($xml)) {
            foreach ($xml as &$item) {
                $item = $this->transformSingleXml($item, $xsltProcessor);
            }
            unset($item);
        } else {
            $xml = $this->transformSingleXml($xml, $xsltProcessor);
        }

        return $xml;
    }

    /**
     * Applies XSL style sheet to template's parts.
     *
     * Note: since the method doesn't make any guess on logic of the provided XSL style sheet,
     * make sure that output is correctly escaped. Otherwise you may get broken document.
     *
     * @param \DOMDocument $xslDomDocument
     * @param array $xslOptions
     * @param string $xslOptionsUri
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function applyXslStyleSheet($xslDomDocument, $xslOptions = array(), $xslOptionsUri = '') {
        $xsltProcessor = new \XSLTProcessor();

        $xsltProcessor->importStylesheet($xslDomDocument);
        if (false === $xsltProcessor->setParameter($xslOptionsUri, $xslOptions)) {
            throw new Exception('Could not set values for the given XSL style sheet parameters.');
        }

        $this->tempDocumentHeaders = $this->transformXml($this->tempDocumentHeaders, $xsltProcessor);
        $this->tempDocumentMainPart = $this->transformXml($this->tempDocumentMainPart, $xsltProcessor);
        $this->tempDocumentFooters = $this->transformXml($this->tempDocumentFooters, $xsltProcessor);
    }

    /**
     * @param string $macro
     *
     * @return string
     */
    protected static function ensureMacroCompleted($macro, $closing = false)
    {
        if (static::$ensureMacroCompletion && substr($macro, 0, 2) !== '${' && substr($macro, -1) !== '}') {
            $macro = '${' . ($closing ? '/' : '') . $macro . '}';
        }
        return $macro;
    }

    /**
     * @param string $subject
     *
     * @return string
     */
    protected static function ensureUtf8Encoded($subject) {
        if (!Text::isUTF8($subject)) {
            $subject = utf8_encode($subject);
        }

        return $subject;
    }

    /**
     * @param mixed $search
     * @param mixed $replace
     * @param int $limit
     */
    public function setValue($search, $replace, $limit = self::MAXIMUM_REPLACEMENTS_DEFAULT) {
        if (is_array($search)) {
            foreach ($search as &$item) {
                $item = static::ensureMacroCompleted($item);
            }
            unset($item);
        } else {
            $search = static::ensureMacroCompleted($search);
        }

        if (is_array($replace)) {
            foreach ($replace as &$item) {
                $item = static::ensureUtf8Encoded($item);
            }
            unset($item);
        } else {
            $replace = static::ensureUtf8Encoded($replace);
        }

        if (Settings::isOutputEscapingEnabled()) {
            $xmlEscaper = new Xml();
            $replace = $xmlEscaper->escape($replace);
        }

        $this->tempDocumentHeaders = $this->setValueForPart($search, $replace, $this->tempDocumentHeaders, $limit);
        $this->tempDocumentMainPart = $this->setValueForPart($search, $replace, $this->tempDocumentMainPart, $limit);
        $this->tempDocumentFooters = $this->setValueForPart($search, $replace, $this->tempDocumentFooters, $limit);
    }

    private function getImageArgs($varNameWithArgs) {
        $varElements = explode(':', $varNameWithArgs);
        array_shift($varElements); // first element is name of variable => remove it

        $varInlineArgs = array();
        // size format documentation: https://msdn.microsoft.com/en-us/library/documentformat.openxml.vml.shape%28v=office.14%29.aspx?f=255&MSPPError=-2147217396
        foreach ($varElements as $argIdx => $varArg) {
            if (strpos($varArg, '=')) { // arg=value
                list($argName, $argValue) = explode('=', $varArg, 2);
                $argName = strtolower($argName);
                if ($argName == 'size') {
                    list($varInlineArgs['width'], $varInlineArgs['height']) = explode('x', $argValue, 2);
                } else {
                    $varInlineArgs[strtolower($argName)] = $argValue;
                }
            } elseif (preg_match('/^([0-9]*[a-z%]{0,2}|auto)x([0-9]*[a-z%]{0,2}|auto)$/i', $varArg)) { // 60x40
                list($varInlineArgs['width'], $varInlineArgs['height']) = explode('x', $varArg, 2);
            } else { // :60:40:f
                switch ($argIdx) {
                    case 0:
                        $varInlineArgs['width'] = $varArg;
                        break;
                    case 1:
                        $varInlineArgs['height'] = $varArg;
                        break;
                    case 2:
                        $varInlineArgs['ratio'] = $varArg;
                        break;
                }
            }
        }

        return $varInlineArgs;
    }

    private function chooseImageDimension($baseValue, $inlineValue, $defaultValue) {
        $value = $baseValue;
        if (is_null($value) && isset($inlineValue)) {
            $value = $inlineValue;
        }
        if (!preg_match('/^([0-9]*(cm|mm|in|pt|pc|px|%|em|ex|)|auto)$/i', $value)) {
            $value = null;
        }
        if (is_null($value)) {
            $value = $defaultValue;
        }
        if (is_numeric($value)) {
            $value .= 'px';
        }

        return $value;
    }

    private function fixImageWidthHeightRatio(&$width, &$height, $actualWidth, $actualHeight) {
        $imageRatio = $actualWidth / $actualHeight;

        if (($width === '') && ($height === '')) { // defined size are empty
            $width = $actualWidth . 'px';
            $height = $actualHeight . 'px';
        } elseif ($width === '') { // defined width is empty
            $heightFloat = (float) $height;
            $widthFloat = $heightFloat * $imageRatio;
            $matches = array();
            preg_match("/\d([a-z%]+)$/", $height, $matches);
            $width = $widthFloat . $matches[1];
        } elseif ($height === '') { // defined height is empty
            $widthFloat = (float) $width;
            $heightFloat = $widthFloat / $imageRatio;
            $matches = array();
            preg_match("/\d([a-z%]+)$/", $width, $matches);
            $height = $heightFloat . $matches[1];
        } else { // we have defined size, but we need also check it aspect ratio
            $widthMatches = array();
            preg_match("/\d([a-z%]+)$/", $width, $widthMatches);
            $heightMatches = array();
            preg_match("/\d([a-z%]+)$/", $height, $heightMatches);
            // try to fix only if dimensions are same
            if ($widthMatches[1] == $heightMatches[1]) {
                $dimention = $widthMatches[1];
                $widthFloat = (float) $width;
                $heightFloat = (float) $height;
                $definedRatio = $widthFloat / $heightFloat;

                if ($imageRatio > $definedRatio) { // image wider than defined box
                    $height = ($widthFloat / $imageRatio) . $dimention;
                } elseif ($imageRatio < $definedRatio) { // image higher than defined box
                    $width = ($heightFloat * $imageRatio) . $dimention;
                }
            }
        }
    }

    private function prepareImageAttrs($replaceImage, $varInlineArgs) {
        // get image path and size
        $width = null;
        $height = null;
        $ratio = null;
        if (is_array($replaceImage) && isset($replaceImage['path'])) {
            $imgPath = $replaceImage['path'];
            if (isset($replaceImage['width'])) {
                $width = $replaceImage['width'];
            }
            if (isset($replaceImage['height'])) {
                $height = $replaceImage['height'];
            }
            if (isset($replaceImage['ratio'])) {
                $ratio = $replaceImage['ratio'];
            }
        } else {
            $imgPath = $replaceImage;
        }

        $width = $this->chooseImageDimension($width, isset($varInlineArgs['width']) ? $varInlineArgs['width'] : null, 115);
        $height = $this->chooseImageDimension($height, isset($varInlineArgs['height']) ? $varInlineArgs['height'] : null, 70);

        $imageData = @getimagesize($imgPath);
        if (!is_array($imageData)) {
            throw new Exception(sprintf('Invalid image: %s', $imgPath));
        }
        list($actualWidth, $actualHeight, $imageType) = $imageData;

        // fix aspect ratio (by default)
        if (is_null($ratio) && isset($varInlineArgs['ratio'])) {
            $ratio = $varInlineArgs['ratio'];
        }
        if (is_null($ratio) || !in_array(strtolower($ratio), array('', '-', 'f', 'false'))) {
            $this->fixImageWidthHeightRatio($width, $height, $actualWidth, $actualHeight);
        }

        $imageAttrs = array(
            'src' => $imgPath,
            'mime' => image_type_to_mime_type($imageType),
            'width' => $width,
            'height' => $height,
        );

        return $imageAttrs;
    }

    private function addImageToRelations($partFileName, $rid, $imgPath, $imageMimeType) {
        // define templates
        $typeTpl = '<Override PartName="/word/media/{IMG}" ContentType="image/{EXT}"/>';
        $relationTpl = '<Relationship Id="{RID}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/{IMG}"/>';
        $newRelationsTpl = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
        $newRelationsTypeTpl = '<Override PartName="/{RELS}" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $extTransform = array(
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/bmp' => 'bmp',
            'image/gif' => 'gif',
        );

        // get image embed name
        if (isset($this->tempDocumentNewImages[$imgPath])) {
            $imgName = $this->tempDocumentNewImages[$imgPath];
        } else {
            // transform extension
            if (isset($extTransform[$imageMimeType])) {
                $imgExt = $extTransform[$imageMimeType];
            } else {
                throw new Exception("Unsupported image type $imageMimeType");
            }

            // add image to document
            $imgName = 'image_' . $rid . '_' . pathinfo($partFileName, PATHINFO_FILENAME) . '.' . $imgExt;
            $this->zipClass->pclzipAddFile($imgPath, 'word/media/' . $imgName);
            $this->tempDocumentNewImages[$imgPath] = $imgName;

            // setup type for image
            $xmlImageType = str_replace(array('{IMG}', '{EXT}'), array($imgName, $imgExt), $typeTpl);
            $this->tempDocumentContentTypes = str_replace('</Types>', $xmlImageType, $this->tempDocumentContentTypes) . '</Types>';
        }

        $xmlImageRelation = str_replace(array('{RID}', '{IMG}'), array($rid, $imgName), $relationTpl);

        if (!isset($this->tempDocumentRelations[$partFileName])) {
            // create new relations file
            $this->tempDocumentRelations[$partFileName] = $newRelationsTpl;
            // and add it to content types
            $xmlRelationsType = str_replace('{RELS}', $this->getRelationsName($partFileName), $newRelationsTypeTpl);
            $this->tempDocumentContentTypes = str_replace('</Types>', $xmlRelationsType, $this->tempDocumentContentTypes) . '</Types>';
        }

        // add image to relations
        $this->tempDocumentRelations[$partFileName] = str_replace('</Relationships>', $xmlImageRelation, $this->tempDocumentRelations[$partFileName]) . '</Relationships>';
    }

    /**
     * @param mixed $search
     * @param mixed $replace Path to image, or array("path" => xx, "width" => yy, "height" => zz)
     * @param int $limit
     */
    public function setImageValue($search, $replace, $limit = self::MAXIMUM_REPLACEMENTS_DEFAULT) {
        // prepare $search_replace
        if (!is_array($search)) {
            $search = array($search);
        }

        $replacesList = array();
        if (!is_array($replace) || isset($replace['path'])) {
            $replacesList[] = $replace;
        } else {
            $replacesList = array_values($replace);
        }

        $searchReplace = array();
        foreach ($search as $searchIdx => $searchString) {
            $searchReplace[$searchString] = isset($replacesList[$searchIdx]) ? $replacesList[$searchIdx] : $replacesList[0];
        }

        // collect document parts
        $searchParts = array(
            $this->getMainPartName() => &$this->tempDocumentMainPart,
        );
        foreach (array_keys($this->tempDocumentHeaders) as $headerIndex) {
            $searchParts[$this->getHeaderName($headerIndex)] = &$this->tempDocumentHeaders[$headerIndex];
        }
        foreach (array_keys($this->tempDocumentFooters) as $headerIndex) {
            $searchParts[$this->getFooterName($headerIndex)] = &$this->tempDocumentFooters[$headerIndex];
        }

        // define templates
        // result can be verified via "Open XML SDK 2.5 Productivity Tool" (http://www.microsoft.com/en-us/download/details.aspx?id=30425)
        $imgTpl = '<w:pict><v:shape type="#_x0000_t75" style="width:{WIDTH};height:{HEIGHT}"><v:imagedata r:id="{RID}" o:title=""/></v:shape></w:pict>';

        foreach ($searchParts as $partFileName => &$partContent) {
            $partVariables = $this->getVariablesForPart($partContent);

            foreach ($searchReplace as $searchString => $replaceImage) {
                $varsToReplace = array_filter($partVariables, function ($partVar) use ($searchString) {
                    return ($partVar == $searchString) || preg_match('/^' . preg_quote($searchString) . ':/', $partVar);
                });

                foreach ($varsToReplace as $varNameWithArgs) {
                    $varInlineArgs = $this->getImageArgs($varNameWithArgs);
                    $preparedImageAttrs = $this->prepareImageAttrs($replaceImage, $varInlineArgs);
                    $imgPath = $preparedImageAttrs['src'];

                    // get image index
                    $imgIndex = $this->getNextRelationsIndex($partFileName);
                    $rid = 'rId' . $imgIndex;

                    // replace preparations
                    $this->addImageToRelations($partFileName, $rid, $imgPath, $preparedImageAttrs['mime']);
                    $xmlImage = str_replace(array('{RID}', '{WIDTH}', '{HEIGHT}'), array($rid, $preparedImageAttrs['width'], $preparedImageAttrs['height']), $imgTpl);

                    // replace variable
                    $varNameWithArgsFixed = static::ensureMacroCompleted($varNameWithArgs);
                    $matches = array();
                    if (preg_match('/(<[^<]+>)([^<]*)(' . preg_quote($varNameWithArgsFixed) . ')([^>]*)(<[^>]+>)/Uu', $partContent, $matches)) {
                        $wholeTag = $matches[0];
                        array_shift($matches);
                        list($openTag, $prefix,, $postfix, $closeTag) = $matches;
                        $replaceXml = $openTag . $prefix . $closeTag . $xmlImage . $openTag . $postfix . $closeTag;
                        // replace on each iteration, because in one tag we can have 2+ inline variables => before proceed next variable we need to change $partContent
                        $partContent = $this->setValueForPart($wholeTag, $replaceXml, $partContent, $limit);
                    }
                }
            }
        }
    }

    /**
     * Returns count of all variables in template.
     *
     * @return array
     */
    public function getVariableCount() {
        $variables = $this->getVariablesForPart($this->tempDocumentMainPart);

        foreach ($this->tempDocumentHeaders as $headerXML) {
            $variables = array_merge(
                    $variables, $this->getVariablesForPart($headerXML)
            );
        }

        foreach ($this->tempDocumentFooters as $footerXML) {
            $variables = array_merge(
                    $variables, $this->getVariablesForPart($footerXML)
            );
        }

        return array_count_values($variables);
    }

    /**
     * Returns array of all variables in template.
     *
     * @return string[]
     */
    public function getVariables() {
        return array_keys($this->getVariableCount());
    }

    /**
     * Clone a table row in a template document.
     *
     * @param string $search
     * @param int $numberOfClones
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function cloneRow($search, $numberOfClones) {
        $search = static::ensureMacroCompleted($search);

        $tagPos = strpos($this->tempDocumentMainPart, $search);
        if (!$tagPos) {
            throw new Exception('Can not clone row, template variable not found or variable contains markup.');
        }

        $rowStart = $this->findRowStart($tagPos);
        $rowEnd = $this->findRowEnd($tagPos);
        $xmlRow = $this->getSlice($rowStart, $rowEnd);

        // Check if there's a cell spanning multiple rows.
        if (preg_match('#<w:vMerge w:val="restart"/>#', $xmlRow)) {
            // $extraRowStart = $rowEnd;
            $extraRowEnd = $rowEnd;
            while (true) {
                $extraRowStart = $this->findRowStart($extraRowEnd + 1);
                $extraRowEnd = $this->findRowEnd($extraRowEnd + 1);

                // If extraRowEnd is lower then 7, there was no next row found.
                if ($extraRowEnd < 7) {
                    break;
                }

                // If tmpXmlRow doesn't contain continue, this row is no longer part of the spanned row.
                $tmpXmlRow = $this->getSlice($extraRowStart, $extraRowEnd);
                if (!preg_match('#<w:vMerge/>#', $tmpXmlRow) &&
                        !preg_match('#<w:vMerge w:val="continue"\s*/>#', $tmpXmlRow)) {
                    break;
                }
                // This row was a spanned row, update $rowEnd and search for the next row.
                $rowEnd = $extraRowEnd;
            }
            $xmlRow = $this->getSlice($rowStart, $rowEnd);
        }

        $result = $this->getSlice(0, $rowStart);
        $result .= implode($this->indexClonedVariables($numberOfClones, $xmlRow));
        $result .= $this->getSlice($rowEnd);

        $this->tempDocumentMainPart = $result;
    }

    /**
     * process a block.
     *
     * @param string  $blockname The blockname without '${}'
     * @param int $clones
     * @param mixed $replace
     * @param bool $incrementVariables
     * @param bool $throwException
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @return mixed The cloned string if successful, false ($blockname not found) or Null (no paragraph found)
     */
    private function processBlock(
        $blockname,
        $clones = 1,
        $replace = true,
        $incrementVariables = true,
        $throwException = false
    ) {
        $startSearch = static::ensureMacroCompleted($blockname);
        $endSearch = static::ensureMacroCompleted($blockname, true);
        if (substr($blockname, -1) == '/') { // singleton/closed block
            return $this->processSegment(
                $startSearch,
                'w:p',
                0,
                $clones,
                'MainPart',
                $replace,
                $incrementVariables,
                $throwException
            );
        }
        $startTagPos = strpos($this->tempDocumentMainPart, $startSearch);
        $endTagPos = strpos($this->tempDocumentMainPart, $endSearch, $startTagPos);
        if (!$startTagPos || !$endTagPos) {
            return $this->failGraciously(
                "Can not find block '$blockname', template variable not found or variable contains markup.",
                $throwException,
                false
            );
        }
        $startBlockStart = $this->findOpenTagLeft($this->tempDocumentMainPart, '<w:p>', $startTagPos, $throwException);
        $startBlockEnd = $this->findCloseTagRight($this->tempDocumentMainPart, '</w:p>', $startTagPos);
        if (!$startBlockStart || !$startBlockEnd) {
            return $this->failGraciously(
                "Can not find start paragraph around block '$blockname'",
                $throwException,
                null
            );
        }
        $endBlockStart = $this->findOpenTagLeft($this->tempDocumentMainPart, '<w:p>', $endTagPos, $throwException);
        $endBlockEnd = $this->findCloseTagRight($this->tempDocumentMainPart, '</w:p>', $endTagPos);
        if (!$endBlockStart || !$endBlockEnd) {
            return $this->failGraciously(
                "Can not find end paragraph around block '$blockname'",
                $throwException,
                null
            );
        }
        if ($startBlockEnd == $endBlockEnd) { // inline block
            $startBlockStart = $startTagPos;
            $startBlockEnd = $startTagPos + strlen($startSearch);
            $endBlockStart = $endTagPos;
            $endBlockEnd = $endTagPos + strlen($endSearch);
        }
        $xmlBlock = $this->getSlice($this->tempDocumentMainPart, $startBlockEnd, $endBlockStart);
        if ($replace !== false) {
            if ($replace === true) {
                $replace = static::cloneSlice($xmlBlock, $clones, $incrementVariables);
            }
            $this->tempDocumentMainPart =
                $this->getSlice($this->tempDocumentMainPart, 0, $startBlockStart)
                . $replace
                . $this->getSlice($this->tempDocumentMainPart, $endBlockEnd);
            return true;
        }
        return $xmlBlock;
    }
    /**
     * Clone a block.
     *
     * @param string  $blockname The blockname without '${}', it will search for '${BLOCKNAME}' and '${/BLOCKNAME}
     * @param int $clones How many times the block needs to be cloned
     * @param bool $incrementVariables true by default (variables get appended #1, #2 inside the cloned blocks)
     * @param bool $throwException false by default (it then returns false or null on errors)
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @return mixed True if successful, false ($blockname not found) or null (no paragraph found)
     */
    public function cloneBlock(
        $blockname,
        $clones = 1,
        $incrementVariables = true,
        $throwException = false
    ) {
        return $this->processBlock($blockname, $clones, true, $incrementVariables, $throwException);
    }
    /**
     * Find the start position of the nearest tag before $offset.
     *
     * @param string $searchString The string we are searching in (the mainbody or an array element of Footers/Headers)
     * @param string $tag  Fully qualified tag, for example: '<w:p>' (with brackets!)
     * @param int $offset Do not look from the beginning, but starting at $offset
     * @param bool $throwException
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @return int Zero if not found (due to the nature of xml, your document never starts at 0)
     */
    protected function findOpenTagLeft(&$searchString, $tag, $offset = 0, $throwException = false)
    {
        $tagStart = strrpos(
            $searchString,
            substr($tag, 0, -1) . ' ',
            ((strlen($searchString) - $offset) * -1)
        );
        if ($tagStart === false) {
            $tagStart = strrpos(
                $searchString,
                $tag,
                ((strlen($searchString) - $offset) * -1)
            );
            if ($tagStart === false) {
                return $this->failGraciously(
                    'Can not find the start position of the item to clone.',
                    $throwException,
                    0
                );
            }
        }
        return $tagStart;
    }
    /**
     * Find the start position of the nearest tag before $offset.
     *
     * @param string  $searchString The string we are searching in (the mainbody or an array element of Footers/Headers)
     * @param string  $tag  Fully qualified tag, for example: '<w:p>' (with brackets!)
     * @param int $offset Do not look from the beginning, but starting at $offset
     * @param bool $throwException
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @return int Zero if not found (due to the nature of xml, your document never starts at 0)
     */
    protected function findOpenTagRight(&$searchString, $tag, $offset = 0, $throwException = false)
    {
        $tagStart = strpos(
            $searchString,
            substr($tag, 0, -1) . ' ',
            $offset
        );
        if ($tagStart === false) {
            $tagStart = strrpos(
                $searchString,
                $tag,
                $offset
            );
            if ($tagStart === false) {
                return $this->failGraciously(
                    'Can not find the start position of the item to clone.',
                    $throwException,
                    0
                );
            }
        }
        return $tagStart;
    }
    /**
     * Find the end position of the nearest $tag after $offset.
     *
     * @param string $searchString The string we are searching in (the MainPart or an array element of Footers/Headers)
     * @param string $tag  Fully qualified tag, for example: '</w:p>'
     * @param int $offset Do not look from the beginning, but starting at $offset
     *
     * @return int Zero if not found
     */
    protected function findCloseTagLeft(&$searchString, $tag, $offset = 0)
    {
        $pos = strrpos($searchString, $tag, ((strlen($searchString) - $offset) * -1));
        if ($pos !== false) {
            return $pos + strlen($tag);
        }
        return 0;
    }
    /**
     * Find the end position of the nearest $tag after $offset.
     *
     * @param string  $searchString The string we are searching in (the MainPart or an array element of Footers/Headers)
     * @param string  $tag  Fully qualified tag, for example: '</w:p>'
     * @param int $offset Do not look from the beginning, but starting at $offset
     *
     * @return int Zero if not found
     */
    protected function findCloseTagRight(&$searchString, $tag, $offset = 0)
    {
        $pos = strpos($searchString, $tag, $offset);
        if ($pos !== false) {
            return $pos + strlen($tag);
        }
        return 0;
    }
    public function setBlock($blockname, $replace, $limit = self::MAXIMUM_REPLACEMENTS_DEFAULT)
    {
        if (is_string($replace) && preg_match('~\R~u', $replace)) {
            $replace = preg_split('~\R~u', $replace);
        }
        if (is_array($replace)) {
            $this->processBlock($blockname, count($replace), true, false);
            foreach ($replace as $oneVal) {
                $this->setValue($blockname, $oneVal, 1);
            }
        } else {
            $this->setValue($blockname, $replace, $limit);
        }
    }

    /**
     * Replace a block.
     *
     * @param string $blockname
     * @param string $replacement
     */
    public function replaceBlock($blockname, $replacement) {
        preg_match(
                '/(<\?xml.*)(<w:p.*>\${' . $blockname . '}<\/w:.*?p>)(.*)(<w:p.*\${\/' . $blockname . '}<\/w:.*?p>)/is', $this->tempDocumentMainPart, $matches
        );

        if (isset($matches[3])) {
            $this->tempDocumentMainPart = str_replace(
                    $matches[2] . $matches[3] . $matches[4], $replacement, $this->tempDocumentMainPart
            );
        }
    }

    /**
     * Delete a block of text.
     *
     * @param string $blockname
     */
    public function deleteBlock($blockname) {
        $this->replaceBlock($blockname, '');
    }

    /**
     * Saves the result document.
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     *
     * @return string
     */
    public function save() {
        foreach ($this->tempDocumentHeaders as $index => $xml) {
            $this->savePartWithRels($this->getHeaderName($index), $xml);
        }

        $this->savePartWithRels($this->getMainPartName(), $this->tempDocumentMainPart);

        foreach ($this->tempDocumentFooters as $index => $xml) {
            $this->savePartWithRels($this->getFooterName($index), $xml);
        }

        $this->zipClass->addFromString($this->getDocumentContentTypesName(), $this->tempDocumentContentTypes);

        // Close zip file
        if (false === $this->zipClass->close()) {
            throw new Exception('Could not close zip file.'); // @codeCoverageIgnore
        }

        return $this->tempDocumentFilename;
    }

    /**
     * @param string $fileName
     * @param string $xml
     */
    protected function savePartWithRels($fileName, $xml) {
        $this->zipClass->addFromString($fileName, $xml);
        if (isset($this->tempDocumentRelations[$fileName])) {
            $relsFileName = $this->getRelationsName($fileName);
            $this->zipClass->addFromString($relsFileName, $this->tempDocumentRelations[$fileName]);
        }
    }

    /**
     * Saves the result document to the user defined file.
     *
     * @since 0.8.0
     *
     * @param string $fileName
     */
    public function saveAs($fileName) {
        $tempFileName = $this->save();

        if (file_exists($fileName)) {
            unlink($fileName);
        }

        /*
         * Note: we do not use `rename` function here, because it loses file ownership data on Windows platform.
         * As a result, user cannot open the file directly getting "Access denied" message.
         *
         * @see https://github.com/PHPOffice/PHPWord/issues/532
         */
        copy($tempFileName, $fileName);
        unlink($tempFileName);
    }

    /**
     * Finds parts of broken macros and sticks them together.
     * Macros, while being edited, could be implicitly broken by some of the word processors.
     *
     * @param string $documentPart The document part in XML representation
     *
     * @return string
     */
    protected function fixBrokenMacros($documentPart) {
        return preg_replace_callback(
                '/\$(?:\{|[^{$]*\>\{)[^}$]*\}/U', function ($match) {
            return strip_tags($match[0]);
        }, $documentPart
        );
    }

    /**
     * Find and replace macros in the given XML section.
     *
     * @param mixed $search
     * @param mixed $replace
     * @param string $documentPartXML
     * @param int $limit
     *
     * @return string
     */
    protected function setValueForPart($search, $replace, $documentPartXML, $limit) {
        // Note: we can't use the same function for both cases here, because of performance considerations.
        if (self::MAXIMUM_REPLACEMENTS_DEFAULT === $limit) {
            return str_replace($search, $replace, $documentPartXML);
        }
        $regExpEscaper = new RegExp();

        return preg_replace($regExpEscaper->escape($search), $replace, $documentPartXML, $limit);
    }

    /**
     * Find all variables in $documentPartXML.
     *
     * @param string $documentPartXML
     *
     * @return string[]
     */
    protected function getVariablesForPart($documentPartXML) {
        preg_match_all('/\$\{(.*?)}/i', $documentPartXML, $matches);

        return $matches[1];
    }

    /**
     * Get the name of the header file for $index.
     *
     * @param int $index
     *
     * @return string
     */
    protected function getHeaderName($index) {
        return sprintf('word/header%d.xml', $index);
    }

    /**
     * Usually, the name of main part document will be 'document.xml'. However, some .docx files (possibly those from Office 365, experienced also on documents from Word Online created from blank templates) have file 'document22.xml' in their zip archive instead of 'document.xml'. This method searches content types file to correctly determine the file name.
     *
     * @return string
     */
    protected function getMainPartName() {
        $contentTypes = $this->zipClass->getFromName('[Content_Types].xml');

        $pattern = '~PartName="\/(word\/document.*?\.xml)" ContentType="application\/vnd\.openxmlformats-officedocument\.wordprocessingml\.document\.main\+xml"~';

        preg_match($pattern, $contentTypes, $matches);

        return array_key_exists(1, $matches) ? $matches[1] : 'word/document.xml';
    }

    /**
     * Get the name of the footer file for $index.
     *
     * @param int $index
     *
     * @return string
     */
    protected function getFooterName($index) {
        return sprintf('word/footer%d.xml', $index);
    }

    /**
     * Get the name of the relations file for document part.
     *
     * @param string $documentPartName
     *
     * @return string
     */
    protected function getRelationsName($documentPartName) {
        return 'word/_rels/' . pathinfo($documentPartName, PATHINFO_BASENAME) . '.rels';
    }

    protected function getNextRelationsIndex($documentPartName) {
        if (isset($this->tempDocumentRelations[$documentPartName])) {
            return substr_count($this->tempDocumentRelations[$documentPartName], '<Relationship');
        }

        return 1;
    }

    /**
     * @return string
     */
    protected function getDocumentContentTypesName() {
        return '[Content_Types].xml';
    }

    /**
     * Find the start position of the nearest table row before $offset.
     *
     * @param int $offset
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     *
     * @return int
     */
    protected function findRowStart($offset) {
        $rowStart = strrpos($this->tempDocumentMainPart, '<w:tr ', ((strlen($this->tempDocumentMainPart) - $offset) * -1));

        if (!$rowStart) {
            $rowStart = strrpos($this->tempDocumentMainPart, '<w:tr>', ((strlen($this->tempDocumentMainPart) - $offset) * -1));
        }
        if (!$rowStart) {
            throw new Exception('Can not find the start position of the row to clone.');
        }

        return $rowStart;
    }

    /**
     * Find the end position of the nearest table row after $offset.
     *
     * @param int $offset
     *
     * @return int
     */
    protected function findRowEnd($offset) {
        return strpos($this->tempDocumentMainPart, '</w:tr>', $offset) + 7;
    }
    protected static function cloneSlice(&$text, $numberOfClones = 1, $incrementVariables = true)
    {
        $result = '';
        for ($i = 1; $i <= $numberOfClones; $i++) {
            if ($incrementVariables) {
                $result .= preg_replace('/\$\{(.*?)(\/?)\}/', '\${\\1#' . $i . '\\2}', $text);
            } else {
                $result .= $text;
            }
        }
        return $result;
    }
    /**
     * Get a slice of a string.
     *
     * @param int $startPosition
     * @param int $endPosition
     *
     * @return string
     */
    protected function getSliceImage($startPosition, $endPosition = 0) {
        if (!$endPosition) {
            $endPosition = strlen($this->tempDocumentMainPart);
        }

        return substr($this->tempDocumentMainPart, $startPosition, ($endPosition - $startPosition));
    }
    /**
     * Get a slice of a string.
     *
     * @param string $searchString The string we are searching in (the MainPart or an array element of Footers/Headers)
     * @param int $startPosition
     * @param int $endPosition
     *
     * @return string
     */
    protected function getSlice(&$searchString, $startPosition, $endPosition = 0)
    {
        if (!$endPosition) {
            $endPosition = strlen($searchString);
        }
        return substr($searchString, $startPosition, ($endPosition - $startPosition));
    }

    /**
     * Replaces variable names in cloned
     * rows/blocks with indexed names
     *
     * @param int $count
     * @param string $xmlBlock
     *
     * @return string
     */
    protected function indexClonedVariables($count, $xmlBlock) {
        $results = array();
        for ($i = 1; $i <= $count; $i++) {
            $results[] = preg_replace('/\$\{(.*?)\}/', '\${\\1#' . $i . '}', $xmlBlock);
        }

        return $results;
    }

    /**
     * Raplaces variables with values from array, array keys are the variable names
     *
     * @param array $variableReplacements
     * @param string $xmlBlock
     *
     * @return string[]
     */
    protected function replaceClonedVariables($variableReplacements, $xmlBlock) {
        $results = array();
        foreach ($variableReplacements as $replacementArray) {
            $localXmlBlock = $xmlBlock;
            foreach ($replacementArray as $search => $replacement) {
                $localXmlBlock = $this->setValueForPart(self::ensureMacroCompleted($search), $replacement, $localXmlBlock, self::MAXIMUM_REPLACEMENTS_DEFAULT);
            }
            $results[] = $localXmlBlock;
        }

        return $results;
    }

}