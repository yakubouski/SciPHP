<?php
namespace Sci\Db;

class Dsn
{
    private $dbHandle,$dbConn;
    public function __construct($DbDriver,$DbUser,$DbPwd,$DbHost='localhost',$DbName=null,$DpOpt=[]) {
        @list($DbHost,$DbPort) = explode(':',$DbHost,2);
        @list($DbFlag,$DbDriver) = explode(':',$DbDriver,2);
        if(empty($DbDriver)) {
            $DbDriver = $DbFlag;
            $DbFlag = null;
        }
        $this->dbConn = [
            'driver'=>$DbDriver,
            'host'=>$DbHost,
            'user'=>$DbUser,
            'pwd'=>$DbPwd,
            'port'=>$DbPort,
            'opt'=>$DpOpt,
            'db'=>$DbName,
            'persistent'=>($DbFlag === 'p')
        ];
    }

    private function __dsn() {
        return $this->dbConn['driver'].':'.('host='.$this->dbConn['host']).(!empty($this->dbConn['port']) ?(';port='.$this->dbConn['port']):'').(!empty($this->dbConn['db']) ? (';dbname='.$this->dbConn['db']):'');
    }

    private function mysql() {
        ($this->dbConn['persistent']) &&
            $this->dbConn['opt'][\PDO::ATTR_PERSISTENT] = true;
        !isset($this->dbConn['opt'][\PDO::MYSQL_ATTR_INIT_COMMAND]) &&
            $this->dbConn['opt'][\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES \'UTF8\'';

        $this->dbHandle = new \PDO($this->__dsn(),$this->dbConn['user'],$this->dbConn['pwd'],$this->dbConn['opt']);
    }

    private function h() {
        if(is_null($this->dbHandle)) {
            try {
                switch($this->dbConn['driver']) {
                    case 'mysql':
                        $this->mysql();
                        break;
                    default:
                        throw new \Exception('no PDO driver found');
                }
            }
            catch(\Exception $e) {
                trigger_error(__CLASS__.': '.$e->getMessage());
                throw $e;
            }
            catch (PDOException $e) {
                trigger_error(__CLASS__.': '.$e->getMessage());
                throw $e;
            }
        }
        return $this->dbHandle;
    }

    /**
     * Summary of SqlExecute
     * @param string $Query
     * @param array $Args
     * @return \PDOStatement
     */
    public function SqlExecute($Query,...$Args) {
        try {
            $sth = $this->h()->prepare($Query);
            $sth->execute(!empty($Args) && is_array($Args[0]) ? $Args[0] : $Args);
            return $sth;
        }
        catch(Exception $e) {
        }
        return [];
    }
}