<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class MegaByteInputElement extends AbstractFormElement
{
    /**
     * Render the input form.
     *
     * @see \Mehrwert\FalQuota\Evaluation\StorageQuotaEvaluation
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function render(): array
    {
        $parameterArray = $this->data['parameterArray'];
        $size = $parameterArray['fieldConf']['config']['size'];

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($this->initializeResultArray(), $fieldInformationResult, false);

        $fieldId = StringUtility::getUniqueId('formengine-textarea-');

        $attributes = [
            'id' => $fieldId,
            'name' => htmlspecialchars((string)$parameterArray['itemFormElName'], ENT_QUOTES | ENT_HTML5),
            'size' => $size,
            'data-formengine-input-name' => htmlspecialchars((string)$parameterArray['itemFormElName'], ENT_QUOTES | ENT_HTML5),
        ];

        $classes = [
            'form-control',
            't3js-formengine-textarea',
            'formengine-textarea',
        ];
        $itemValue = (int)($parameterArray['itemFormElValue']  / (1024 ** 2));
        $attributes['class'] = implode(' ', $classes);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =      '<div class="form-wizards-element">';
        $html[] =         '<div class="form-control-wrap">';
        $html[] =            '<input type="number" value="' . htmlspecialchars((string)$itemValue, ENT_QUOTES) . '" ';
        $html[] =               GeneralUtility::implodeAttributes($attributes, true);
        $html[] =            ' />';
        $html[] =         '</div>';
        $html[] =      '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';
        $resultArray['html'] = implode(LF, $html);

        return $resultArray;
    }
}
