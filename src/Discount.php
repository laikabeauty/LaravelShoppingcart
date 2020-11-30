<?php

namespace Gloudemans\Shoppingcart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class Discount implements Arrayable, Jsonable
{
    /**
     * The type of the discount item.
     *
     * @var string
     */
    public string $type;

    /**
     * The name of the discount item.
     *
     * @var string
     */
    public string $name;

    /**
     * The amount of the discount item.
     *
     * @var float
     */
    public float $amount;

    /**
     * The options for this discount item.
     *
     * @var array | \Illuminate\Support\Collection
     */
    public $options;

    public int $priority = 0;


    /**
     * CartItem constructor.
     *
     * @param string                               $type
     * @param float                                $amount
     * @param string                               $name
     * @param int                                  $priority
     * @param array|\Illuminate\Support\Collection $options
     */
    public function __construct(string $type, float $amount, string $name, int $priority = 0, $options = [])
    {
        $this->type = $type;
        $this->amount = $amount;
        $this->name = $name;
        $this->priority = $priority;
        $this->options = collect($options);
    }

    public function calculateAmount($item = null, $current = 0): float
    {
        $amount = $this->amount;

        if (!$item) $item = \Gloudemans\Shoppingcart\Facades\Cart::instance();

        if (isset($this->options['minimum_amount']) && $this->options['minimum_amount'] > $item->total) {
            $amount = 0;
        }
        if (isset($this->options['maximum_amount']) && $this->options['maximum_amount'] < $item->total) {
            $amount = 0;
        }

        if ($this->type === 'fixed_product') {
            if (!$item instanceof CartItem || !$this->includesItem($item)) {
                $amount = 0;
            }
        }

        if ($this->type === 'fixed_cart') {
            $amount = $item instanceof Cart ? $amount : 0;
        }

        if ($this->type === 'percent') {
            if ($item instanceof CartItem && !$this->includesItem($item)) {
                $priceTotal = $this->priority ? ($item->priceTotal - $current) : $item->priceTotal;
                $amount = $priceTotal * $amount / 100;
            }
        }

//        echo("{$this->type} => $amount {$item->priceTotal} " . get_class($item).PHP_EOL);

        return $amount;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'type' => $this->type,
            'amount' => $this->amount,
            'name' => $this->name,
            'options' => $this->options,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * @param $item
     */
    public function includesItem(CartItem $item): bool
    {
        $include = true;
        if (isset($this->options['product_ids'])) {
            $include = in_array($item->id, $this->options['product_ids']);
        }
        if (isset($this->options['exclude_product_ids'])) {
            $include = !in_array($item->id, $this->options['exclude_product_ids']);
        }
        return $include;
    }
}
