<?php

namespace Dynamic\Foxy\Coupons\Extension;

use Dynamic\Foxy\Coupons\Model\Coupon;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;

/**
 * Class DiscountTierDataExtension
 * @package Dynamic\Foxy\Coupons\Extension
 */
class DiscountTierDataExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $has_one = [
        'Coupon' => Coupon::class,
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'DiscountLabel' => [
            'title' => 'Discount',
        ],
        'Quantity' => [
            'title' => 'Starting at # of items',
        ],
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName([
            'CouponID',
        ]);

        if ($this->getParent() instanceof Coupon) {
            $quantity = $fields->dataFieldByName('Quantity');
            $quantity->setTitle('Quantity to trigger discount');

            $type = $this->getParent()->Type;

            $fields->removeByName([
                'Percentage',
                'Amount',
            ]);

            switch ($type) {
                case 'quantity_amount':
                case 'price_amount':
                    $fields->addFieldToTab(
                        'Root.Main',
                        NumericField::create('Amount')
                            ->setTitle('Amount to Discount')
                    );
                    break;
                case 'quantity_percentage':
                case 'price_percentage':
                    $fields->addFieldToTab(
                        'Root.Main',
                        NumericField::create('Percentage')
                            ->setTitle('Percentage to Discount')
                    );
                    break;
            }
        }
    }

    /**
     * @return string
     */
    protected function getParent()
    {
        foreach ($this->owner->hasOne() as $relationName => $className) {
            $field = "{$relationName}ID";

            if ($this->owner->{$field} > 0) {
                return $className::get()->byID($this->owner->{$field});
            }
        }

        return false;
    }

    /**
     * @param $label
     */
    public function updateDiscountLabel(&$label)
    {
        if (($parent = $this->getParent()) && $parent instanceof Coupon) {
            switch ($parent->Type) {
                case 'quantity_amount':
                case 'price_amount':
                    $label = "{$this->owner->dbObject('Amount')->Nice()}";

                    break;
                case 'quantity_percentage':
                case 'price_percentage':
                    $label = "{$this->owner->Percentage}%";
                    break;
            }
        }
    }
}
