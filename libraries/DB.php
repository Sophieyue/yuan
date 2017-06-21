<?php

class DB {

    private $connect;

    private function getHost()
    {
        if (empty(core::app()->params["db"]["host"])) {
            die("database config error : illegal host");
        }

        return core::app()->params["db"]["host"];
    }

    private function getUser()
    {
        if (empty(core::app()->params["db"]["user"])) {
            die("database config error : illegal user");
        }

        return core::app()->params["db"]["user"];
    }

    public function getPassword()
    {
        if (empty(core::app()->params["db"]["password"])) {
            die("database config error : illegal password");
        }

        return core::app()->params["db"]["password"];
    }

    public function getDatabase()
    {
        if (empty(core::app()->params["db"]["database"])) {
            die("database config error : illegal database");
        }

        return core::app()->params["db"]["database"];
    }

    private function init()
    {
        $this->connect = mysqli_connect($this->getHost(), $this->getUser(), $this->getPassword());
        if (!$this->connect){
            die('database error:'.mysqli_error());
        }
        mysqli_select_db($this->connect, $this->getDatabase());
        mysqli_query($this->connect, "set names utf8");
    }

    public function isExist($sqlStr)
    {
        $this->init();
        $result = mysqli_query($this->connect, $sqlStr);
        $count = mysqli_num_rows($result);
        mysqli_close($this->connect);
        return $count;
    }

    public function mysqli_real_escape_string_gk($data)
    {
        $this->init();
        $r_data = mysqli_real_escape_string($data,$this->connect);
        mysqli_close($this->connect);
        return $r_data;
    }

    public function insert($sqlStr)
    {
        $this->init();
        $rec_insert = mysqli_query($this->connect, $sqlStr);
        if(! $rec_insert) {
            die('Could not insert data: ' .mysqli_error());
        }
        mysqli_close($this->connect);
    }

    public function delete($sqlStr)
    {
        $this->init();
        mysqli_query($this->connect, $sqlStr);
        mysqli_close($this->connect);
    }

    public function selectAnyContentUrl($sqlStr)
    {
        $this->init();
        mysqli_query($this->connect, "set names utf8");
        $result = mysqli_query($this->connect, $sqlStr);
        if($row = mysqli_fetch_array($result)){
            $result = $row['content_url'];
        }else{
            $result = 0;
        }

        mysqli_close($this->connect);
        return $result;
    }

    public function selectBiz($sqlStr)
    {
        $this->init();
        mysqli_query($this->connect, "set names utf8");
        $result = mysqli_query($this->connect, $sqlStr);
        if($row = mysqli_fetch_array($result)){
            $result = $row['biz'];
        }else{
            $result = 0;
        }

        mysqli_close($this->connect);
        return $result;
    }

    public function update($sqlStr)
    {
        $this->init();
        mysqli_query($this->connect, $sqlStr);
        mysqli_close($this->connect);
    }

    public function select($sqlStr)
    {
        $this->init();
        $result = mysqli_query($this->connect, $sqlStr);
        if($row = mysqli_fetch_array($result)){
            $result = $row['result'];
        }else{
            $result = 0;
        }

        mysqli_close($this->connect);
        return $result;
    }
}

