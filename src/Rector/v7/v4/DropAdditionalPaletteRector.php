<?php

declare(strict_types=1);

namespace Ssch\TYPO3Rector\Rector\v7\v4;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use Ssch\TYPO3Rector\Helper\ArrayUtility;
use Ssch\TYPO3Rector\Helper\TcaHelperTrait;
use Ssch\TYPO3Rector\Rector\Tca\AbstractTcaRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/7.4/Deprecation-67737-TcaDropAdditionalPalette.html
 * @see \Ssch\TYPO3Rector\Tests\Rector\v7\v4\DropAdditionalPaletteRector\DropAdditionalPaletteRectorTest
 */
final class DropAdditionalPaletteRector extends AbstractTcaRector
{
    use TcaHelperTrait;

    /**
     * @var string
     */
    private const FIELD_NAME = 'fieldName';

    /**
     * @var string
     */
    private const FIELD_LABEL = 'fieldLabel';

    /**
     * @var string
     */
    private const PALETTE_NAME = 'paletteName';

    public function refactorTypes(Array_ $types): void
    {
        foreach ($types->items as $typesItem) {
            if (! $typesItem instanceof ArrayItem) {
                continue;
            }

            if (null === $typesItem->key) {
                continue;
            }

            if (! $typesItem->value instanceof Array_) {
                continue;
            }

            $showItemNode = $this->extractArrayItemByKey($typesItem->value, 'showitem');
            if (null === $showItemNode || null === $showItemNode->value) {
                continue;
            }

            $showItemValue = $this->valueResolver->getValue($showItemNode->value);

            if (
                null === $showItemValue
                || ! is_string($showItemValue)
                || false === strpos($showItemValue, ';')
            ) {
                continue;
            }
            $itemList = ArrayUtility::trimExplode(',', $showItemValue, true);
            $newFieldStrings = [];
            foreach ($itemList as $fieldString) {
                $fieldArray = ArrayUtility::trimExplode(';', $fieldString);
                $fieldArray = [
                    self::FIELD_NAME => $fieldArray[0] ?? '',
                    self::FIELD_LABEL => $fieldArray[1] ?? null,
                    self::PALETTE_NAME => $fieldArray[2] ?? null,
                ];
                if ('--palette--' !== $fieldArray[self::FIELD_NAME] && null !== $fieldArray[self::PALETTE_NAME]) {
                    if ($fieldArray[self::FIELD_LABEL]) {
                        $fieldString = $fieldArray[self::FIELD_NAME] . ';' . $fieldArray[self::FIELD_LABEL];
                    } else {
                        $fieldString = $fieldArray[self::FIELD_NAME];
                    }
                    $paletteString = '--palette--;;' . $fieldArray[self::PALETTE_NAME];
                    $newFieldStrings[] = $fieldString;
                    $newFieldStrings[] = $paletteString;
                } else {
                    $newFieldStrings[] = $fieldString;
                }
            }
            if ($newFieldStrings === $itemList) {
                // do not alter the syntax tree, if there are no changes. This will keep formatting of the code intact
                continue;
            }
            $showItemNode->value = new String_(implode(',', $newFieldStrings));
            $this->hasAstBeenChanged = true;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('TCA: Drop additional palette', [
            new CodeSample(
                <<<'CODE_SAMPLE'
return [
    'types' => [
        'aType' => [
            'showitem' => 'aField;aLabel;anAdditionalPaletteName',
        ],
     ],
];
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
return [
    'types' => [
        'aType' => [
            'showitem' => 'aField;aLabel, --palette--;;anAdditionalPaletteName',
        ],
     ],
];
CODE_SAMPLE
            ),
        ]);
    }
}
