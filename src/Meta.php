<?php

namespace Plank\Metable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Plank\Metable\DataType\Registry;
use Watson\Rememberable\Rememberable;
/**
 * Model for storing meta data.
 *
 * @author Sean Fraser <sean@plankdesign.com>
 */
class Meta extends Model
{
    use Rememberable;
    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $table = 'sitemeta';

    /**
     * {@inheritdoc}
     */
    protected $guarded = ['id', 'metable_type', 'metable_id', 'type'];

    /**
     * {@inheritdoc}
     */
    protected $attributes = [
        'type'  => 'null',
        'value' => '',
    ];

    /**
     * Cache of unserialized value.
     *
     * @var mixed
     */
    protected $cachedValue;

    /**
     * Metable Relation.
     *
     * @return MorphTo
     */
    public function metable() : MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Accessor for value.
     *
     * Will unserialize the value before returning it.
     *
     * Successive access will be loaded from cache.
     *
     * @return mixed
     */
    public function getValueAttribute()
    {
        if (!isset($this->cachedValue)) {
            $this->cachedValue = $this->getDataTypeRegistry()
                ->getHandlerForType($this->type)
                ->unserializeValue($this->attributes['value']);
        }

        return $this->cachedValue;
    }

    /**
     * Mutator for value.
     *
     * The `type` attribute will be automatically updated to match the datatype of the input.
     *
     * @param mixed $value
     */
    public function setValueAttribute($value)
    {
        $registry = $this->getDataTypeRegistry();

        $this->attributes['type'] = $registry->getTypeForValue($value);
        $this->attributes['value'] = $registry->getHandlerForType($this->type)
            ->serializeValue($value);

        $this->cachedValue = null;
    }

    /**
     * Retrieve the underlying serialized value.
     *
     * @return string
     */
    public function getRawValue() : string
    {
        return $this->attributes['value'];
    }

    /**
     * Load the datatype Registry from the container.
     *
     * @return Registry
     */
    protected function getDataTypeRegistry() : Registry
    {
        return app('metable.datatype.registry');
    }
}
