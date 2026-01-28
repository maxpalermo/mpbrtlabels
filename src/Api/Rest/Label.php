<?php

namespace MpSoft\MpBrtApiShipment\Api;

class Label
{
    public $dataLength;
    public $parcelID;
    public $stream;
    public $streamDigitalLabel;
    public $parcelNumberGeoPost;
    public $trackingByParcelID;

    public function __construct($dataLength, $parcelID, $stream, $streamDigitalLabel = '', $parcelNumberGeoPost = '', $trackingByParcelID = '')
    {
        $this->dataLength = $dataLength;
        $this->parcelID = $parcelID;
        $this->stream = $stream;
        $this->streamDigitalLabel = $streamDigitalLabel;
        $this->parcelNumberGeoPost = $parcelNumberGeoPost;
        $this->trackingByParcelID = $trackingByParcelID;
    }

    public static function fromArray($arr): Label
    {
        return new self(
            $arr['dataLength'] ?? 0,
            $arr['parcelID'] ?? '',
            $arr['stream'] ?? '',
            $arr['streamDigitalLabel'] ?? '',
            $arr['parcelNumberGeoPost'] ?? '',
            $arr['trackingByParcelID'] ?? ''
        );
    }
}
