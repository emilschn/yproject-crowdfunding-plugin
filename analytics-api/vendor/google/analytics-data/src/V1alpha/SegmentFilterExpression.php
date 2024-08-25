<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1alpha/data.proto

namespace Google\Analytics\Data\V1alpha;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Expresses combinations of segment filters.
 *
 * Generated from protobuf message <code>google.analytics.data.v1alpha.SegmentFilterExpression</code>
 */
class SegmentFilterExpression extends \Google\Protobuf\Internal\Message
{
    protected $expr;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Analytics\Data\V1alpha\SegmentFilterExpressionList $and_group
     *           The SegmentFilterExpression in `andGroup` have an AND relationship.
     *     @type \Google\Analytics\Data\V1alpha\SegmentFilterExpressionList $or_group
     *           The SegmentFilterExpression in `orGroup` have an OR relationship.
     *     @type \Google\Analytics\Data\V1alpha\SegmentFilterExpression $not_expression
     *           The SegmentFilterExpression is NOT of `notExpression`.
     *     @type \Google\Analytics\Data\V1alpha\SegmentFilter $segment_filter
     *           A primitive segment filter.
     *     @type \Google\Analytics\Data\V1alpha\SegmentEventFilter $segment_event_filter
     *           Creates a filter that matches events of a single event name. If a
     *           parameter filter expression is specified, only the subset of events that
     *           match both the single event name and the parameter filter expressions
     *           match this event filter.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Analytics\Data\V1Alpha\Data::initOnce();
        parent::__construct($data);
    }

    /**
     * The SegmentFilterExpression in `andGroup` have an AND relationship.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.SegmentFilterExpressionList and_group = 1;</code>
     * @return \Google\Analytics\Data\V1alpha\SegmentFilterExpressionList|null
     */
    public function getAndGroup()
    {
        return $this->readOneof(1);
    }

    public function hasAndGroup()
    {
        return $this->hasOneof(1);
    }

    /**
     * The SegmentFilterExpression in `andGroup` have an AND relationship.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.SegmentFilterExpressionList and_group = 1;</code>
     * @param \Google\Analytics\Data\V1alpha\SegmentFilterExpressionList $var
     * @return $this
     */
    public function setAndGroup($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Data\V1alpha\SegmentFilterExpressionList::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * The SegmentFilterExpression in `orGroup` have an OR relationship.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.SegmentFilterExpressionList or_group = 2;</code>
     * @return \Google\Analytics\Data\V1alpha\SegmentFilterExpressionList|null
     */
    public function getOrGroup()
    {
        return $this->readOneof(2);
    }

    public function hasOrGroup()
    {
        return $this->hasOneof(2);
    }

    /**
     * The SegmentFilterExpression in `orGroup` have an OR relationship.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.SegmentFilterExpressionList or_group = 2;</code>
     * @param \Google\Analytics\Data\V1alpha\SegmentFilterExpressionList $var
     * @return $this
     */
    public function setOrGroup($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Data\V1alpha\SegmentFilterExpressionList::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * The SegmentFilterExpression is NOT of `notExpression`.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.SegmentFilterExpression not_expression = 3;</code>
     * @return \Google\Analytics\Data\V1alpha\SegmentFilterExpression|null
     */
    public function getNotExpression()
    {
        return $this->readOneof(3);
    }

    public function hasNotExpression()
    {
        return $this->hasOneof(3);
    }

    /**
     * The SegmentFilterExpression is NOT of `notExpression`.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.SegmentFilterExpression not_expression = 3;</code>
     * @param \Google\Analytics\Data\V1alpha\SegmentFilterExpression $var
     * @return $this
     */
    public function setNotExpression($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Data\V1alpha\SegmentFilterExpression::class);
        $this->writeOneof(3, $var);

        return $this;
    }

    /**
     * A primitive segment filter.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.SegmentFilter segment_filter = 4;</code>
     * @return \Google\Analytics\Data\V1alpha\SegmentFilter|null
     */
    public function getSegmentFilter()
    {
        return $this->readOneof(4);
    }

    public function hasSegmentFilter()
    {
        return $this->hasOneof(4);
    }

    /**
     * A primitive segment filter.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.SegmentFilter segment_filter = 4;</code>
     * @param \Google\Analytics\Data\V1alpha\SegmentFilter $var
     * @return $this
     */
    public function setSegmentFilter($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Data\V1alpha\SegmentFilter::class);
        $this->writeOneof(4, $var);

        return $this;
    }

    /**
     * Creates a filter that matches events of a single event name. If a
     * parameter filter expression is specified, only the subset of events that
     * match both the single event name and the parameter filter expressions
     * match this event filter.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.SegmentEventFilter segment_event_filter = 5;</code>
     * @return \Google\Analytics\Data\V1alpha\SegmentEventFilter|null
     */
    public function getSegmentEventFilter()
    {
        return $this->readOneof(5);
    }

    public function hasSegmentEventFilter()
    {
        return $this->hasOneof(5);
    }

    /**
     * Creates a filter that matches events of a single event name. If a
     * parameter filter expression is specified, only the subset of events that
     * match both the single event name and the parameter filter expressions
     * match this event filter.
     *
     * Generated from protobuf field <code>.google.analytics.data.v1alpha.SegmentEventFilter segment_event_filter = 5;</code>
     * @param \Google\Analytics\Data\V1alpha\SegmentEventFilter $var
     * @return $this
     */
    public function setSegmentEventFilter($var)
    {
        GPBUtil::checkMessage($var, \Google\Analytics\Data\V1alpha\SegmentEventFilter::class);
        $this->writeOneof(5, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getExpr()
    {
        return $this->whichOneof("expr");
    }

}

