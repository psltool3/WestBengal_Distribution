<?php

class WholeSale
{
    public $district;
    public $name;
    public $id;
    public $type;
    public $latitude;
    public $longitude;
    public $storage;
    public $uniqueid;
    public $active;

    // Getter methods
    public function getDistrict()
    {
        return $this->district;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function getUniqueid()
    {
        return $this->uniqueid;
    }

    public function getActive()
    {
        return $this->active;
    }


    // Setter methods

    public function setDistrict($district)
    {
        $this->district = $district;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    public function setStorage($storage)
    {
        $this->storage = $storage;
    }

    public function setUniqueid($uniqueid)
    {
        $this->uniqueid = $uniqueid;
    }

    public function setActive($active)
    {
        $this->active = $active;
    }

    function insert(WholeSale $WholeSale)
    {
        return "INSERT INTO WholeSale (district, name, id, type, latitude, longitude, storage, uniqueid, active) VALUES ('" . $WholeSale->getDistrict() . "','" . $WholeSale->getName() . "','" . $WholeSale->getId() . "','" . $WholeSale->getType() . "','" . $WholeSale->getLatitude() . "','" . $WholeSale->getLongitude() . "','" . $WholeSale->getStorage() . "','" . $WholeSale->getUniqueid() . "','" . $WholeSale->getActive() . "')";
    }

    function delete(WholeSale $WholeSale)
    {
        return "DELETE FROM WholeSale WHERE uniqueid='" . $WholeSale->getUniqueid() . "'";
    }

    function deleteall(WholeSale $WholeSale)
    {
        return "DELETE FROM WholeSale WHERE 1";
    }

    function logname(WholeSale $WholeSale)
    {

        return "SELECT name FROM WholeSale WHERE uniqueid='" . $WholeSale->getUniqueid() . "'";

    }

    function check(WholeSale $WholeSale)
    {
        return "SELECT * FROM WholeSale WHERE uniqueid='" . $WholeSale->getUniqueid() . "'";
    }

    function checkEdit(WholeSale $WholeSale)
    {
        return "SELECT * FROM WholeSale WHERE LOWER(id)=LOWER('" . $WholeSale->getId() . "')";
    }

    function checkInsert(WholeSale $WholeSale)
    {
        return "SELECT * FROM WholeSale WHERE LOWER(id)=LOWER('" . $WholeSale->getId() . "')";
    }

    function update(WholeSale $WholeSale)
    {
        return "UPDATE WholeSale SET district = '" . $WholeSale->getDistrict() . "',name = '" . $WholeSale->getName() . "',type = '" . $WholeSale->getType() . "',latitude = '" . $WholeSale->getLatitude() . "',longitude = '" . $WholeSale->getLongitude() . "',storage = '" . $WholeSale->getStorage() . "' WHERE uniqueid = '" . $WholeSale->getUniqueid() . "'";
    }

    function updateEdit(WholeSale $WholeSale)
    {
        return "UPDATE WholeSale SET district = '" . $WholeSale->getDistrict() . "',name = '" . $WholeSale->getName() . "',id = '" . $WholeSale->getId() . "',type = '" . $WholeSale->getType() . "',latitude = '" . $WholeSale->getLatitude() . "',longitude = '" . $WholeSale->getLongitude() . "',storage = '" . $WholeSale->getStorage() . "' WHERE id = '" . $WholeSale->getId() . "'";
    }
}

?>