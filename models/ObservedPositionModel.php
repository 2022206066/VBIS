<?php

namespace app\models;

use app\core\BaseModel;

class ObservedPositionModel extends BaseModel
{
    public int $id;
    public int $satellite_id;
    public float $latitude;
    public float $longitude;
    public float $height;
    public string $timestamp;

    public function tableName()
    {
        return 'observed_positions';
    }

    public function readColumns()
    {
        return ['id', 'satellite_id', 'latitude', 'longitude', 'height', 'timestamp'];
    }

    public function editColumns()
    {
        return ['satellite_id', 'latitude', 'longitude', 'height', 'timestamp'];
    }

    public function validationRules()
    {
        return [
            "satellite_id" => [self::RULE_REQUIRED],
            "latitude" => [self::RULE_REQUIRED],
            "longitude" => [self::RULE_REQUIRED],
            "height" => [self::RULE_REQUIRED],
            "timestamp" => [self::RULE_REQUIRED]
        ];
    }
    
    public function getPositionsWithSatelliteInfo()
    {
        $query = "
            SELECT op.id, op.latitude, op.longitude, op.height, op.timestamp, s.name, s.category
            FROM observed_positions op
            INNER JOIN satellites s ON op.satellite_id = s.id
            ORDER BY op.timestamp DESC
        ";
        
        $dbResult = $this->con->query($query);
        
        $resultArray = [];
        
        while ($result = $dbResult->fetch_assoc()) {
            $resultArray[] = $result;
        }
        
        return $resultArray;
    }
    
    public function getPositionsForSatellite($satelliteId, $limit = 100)
    {
        $query = "
            SELECT * FROM observed_positions 
            WHERE satellite_id = $satelliteId
            ORDER BY timestamp DESC
            LIMIT $limit
        ";
        
        $dbResult = $this->con->query($query);
        
        $resultArray = [];
        
        while ($result = $dbResult->fetch_assoc()) {
            $resultArray[] = $result;
        }
        
        return $resultArray;
    }
    
    public function getPositionsByDateRange($startDate, $endDate)
    {
        $query = "
            SELECT op.id, op.latitude, op.longitude, op.height, op.timestamp, s.name, s.category
            FROM observed_positions op
            INNER JOIN satellites s ON op.satellite_id = s.id
            WHERE op.timestamp BETWEEN '$startDate' AND '$endDate'
            ORDER BY op.timestamp DESC
        ";
        
        $dbResult = $this->con->query($query);
        
        $resultArray = [];
        
        while ($result = $dbResult->fetch_assoc()) {
            $resultArray[] = $result;
        }
        
        return $resultArray;
    }
    

} 