<?php

declare(strict_types=1);

namespace Ssch\TYPO3Rector\Rector\v7\v5;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Stmt\Return_;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Ssch\TYPO3Rector\Helper\TcaHelperTrait;

/**
 * @see https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/7.5/Deprecation-69736-SelectOptionIconsInOptionTagsRemoved.html
 */
final class RemoveIconsInOptionTagsRector extends AbstractRector
{
    use TcaHelperTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Select option iconsInOptionTags removed', [new CodeSample(<<<'PHP'
return [
    'columns' => [
        'foo' => [
            'label' => 'Label',
            'config' => [
                'type' => 'select',
                'maxitems' => 25,
                'autoSizeMax' => 10,
                'iconsInOptionTags' => 1,
            ],
        ],
    ],
];
PHP
                , <<<'PHP'
return [
    'columns' => [
        'foo' => [
            'label' => 'Label',
            'config' => [
                'type' => 'select',
                'maxitems' => 25,
                'autoSizeMax' => 10,
            ],
        ],
    ],
];
PHP
            )]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Return_::class];
    }

    /**
     * @param Return_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isTca($node)) {
            return null;
        }

        $columns = $this->extractColumns($node);

        if (! $columns instanceof ArrayItem) {
            return null;
        }

        $items = $columns->value;

        if (! $items instanceof Array_) {
            return null;
        }

        foreach ($items->items as $fieldValue) {
            if (! $fieldValue instanceof ArrayItem) {
                continue;
            }

            if (null === $fieldValue->key) {
                continue;
            }

            $fieldName = $this->getValue($fieldValue->key);

            if (null === $fieldName) {
                continue;
            }

            if (! $fieldValue->value instanceof Array_) {
                continue;
            }

            foreach ($fieldValue->value->items as $configValue) {
                if (null === $configValue) {
                    continue;
                }

                if (! $configValue->value instanceof Array_) {
                    continue;
                }

                foreach ($configValue->value->items as $configItemValue) {
                    if (! $configItemValue instanceof ArrayItem) {
                        continue;
                    }

                    if (null === $configItemValue->key) {
                        continue;
                    }

                    if ($this->isValue($configItemValue->key, 'iconsInOptionTags')) {
                        $this->removeNode($configItemValue);
                        break;
                    }
                }
            }
        }

        return $node;
    }
}
