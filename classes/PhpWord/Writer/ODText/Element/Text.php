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
 *
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord\Writer\ODText\Element;

use PhpOffice\PhpWord\Element\TrackChange;
use PhpOffice\PhpWord\Exception\Exception;

/**
 * Text element writer.
 *
 * @since 0.10.0
 */
class Text extends AbstractElement
{
    /**
     * Write element.
     */
    public function write(): void
    {
        $xmlWriter = $this->getXmlWriter();
        $element = $this->getElement();
        if (!$element instanceof \PhpOffice\PhpWord\Element\Text) {
            return;
        }
        $fontStyle = $element->getFontStyle();
        $paragraphStyle = $element->getParagraphStyle();

        // @todo Commented for TextRun. Should really checkout this value
        // $fStyleIsObject = ($fontStyle instanceof Font) ? true : false;
        //$fStyleIsObject = false;

        //if ($fStyleIsObject) {
        // Don't never be the case, because I browse all sections for cleaning all styles not declared
        //    throw new Exception('PhpWord : $fStyleIsObject wouldn\'t be an object');
        //}

        if (!$this->withoutP) {
            $xmlWriter->startElement('text:p'); // text:p
        }
        if ($element->getTrackChange() != null && $element->getTrackChange()->getChangeType() == TrackChange::DELETED) {
            $xmlWriter->startElement('text:change');
            $xmlWriter->writeAttribute('text:change-id', $element->getTrackChange()->getElementId());
            $xmlWriter->endElement();
        } else {
            if (empty($fontStyle)) {
                if (empty($paragraphStyle)) {
                    if (!$this->withoutP) {
                        $xmlWriter->writeAttribute('text:style-name', 'Normal');
                    }
                } elseif (is_string($paragraphStyle)) {
                    if (!$this->withoutP) {
                        $xmlWriter->writeAttribute('text:style-name', $paragraphStyle);
                    }
                }
                $this->writeChangeInsertion(true, $element->getTrackChange());
                $this->replaceTabs($element->getText(), $xmlWriter);
                $this->writeChangeInsertion(false, $element->getTrackChange());
            } else {
                if (empty($paragraphStyle)) {
                    if (!$this->withoutP) {
                        $xmlWriter->writeAttribute('text:style-name', 'Normal');
                    }
                } elseif (is_string($paragraphStyle)) {
                    if (!$this->withoutP) {
                        $xmlWriter->writeAttribute('text:style-name', $paragraphStyle);
                    }
                }
                // text:span
                $xmlWriter->startElement('text:span');
                if (is_string($fontStyle)) {
                    $xmlWriter->writeAttribute('text:style-name', $fontStyle);
                }
                $this->writeChangeInsertion(true, $element->getTrackChange());
                $this->replaceTabs($element->getText(), $xmlWriter);
                $this->writeChangeInsertion(false, $element->getTrackChange());
                $xmlWriter->endElement();
            }
        }
        if (!$this->withoutP) {
            $xmlWriter->endElement(); // text:p
        }
    }

    private function replacetabs($text, $xmlWriter): void
    {
        if (preg_match('/^ +/', $text, $matches)) {
            $num = strlen($matches[0]);
            $xmlWriter->startElement('text:s');
            $xmlWriter->writeAttributeIf($num > 1, 'text:c', "$num");
            $xmlWriter->endElement();
            $text = preg_replace('/^ +/', '', $text);
        }
        preg_match_all('/([\\s\\S]*?)(\\t|  +| ?$)/', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->writeText($match[1]);
            if ($match[2] === '') {
                break;
            } elseif ($match[2] === "\t") {
                $xmlWriter->writeElement('text:tab');
            } elseif ($match[2] === ' ') {
                $xmlWriter->writeElement('text:s');

                break;
            } else {
                $num = strlen($match[2]);
                $xmlWriter->startElement('text:s');
                $xmlWriter->writeAttributeIf($num > 1, 'text:c', "$num");
                $xmlWriter->endElement();
            }
        }
    }

    private function writeChangeInsertion($start = true, ?TrackChange $trackChange = null): void
    {
        if ($trackChange == null || $trackChange->getChangeType() != TrackChange::INSERTED) {
            return;
        }
        $xmlWriter = $this->getXmlWriter();
        $xmlWriter->startElement('text:change-' . ($start ? 'start' : 'end'));
        $xmlWriter->writeAttribute('text:change-id', $trackChange->getElementId());
        $xmlWriter->endElement();
    }
}
