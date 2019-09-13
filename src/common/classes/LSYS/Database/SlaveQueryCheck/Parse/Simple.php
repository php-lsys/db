<?php
/**
 * lsys database
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\Database\SlaveQueryCheck\Parse;
use LSYS\Database\SlaveQueryCheck\Parse;
class Simple implements Parse {
    private function _table($table){
        $table=trim($table,'`"');
        return $table;
    }
    /**
     * @return array $table
     */
    public function queryParseTable($sql){
        $table=[];
        $sqls=preg_split('/\s|=|\(|\)|,|;/', trim($sql));
        switch (strtolower(array_shift($sqls))){
            case 'select':
                $tag=0;
                $tab=0;
                $as=0;
                $emm=0;
                while (($sql=array_shift($sqls))!==null){
                    $sql=trim($sql);
                    if(empty($sql))continue;
                    $_sql=strtolower($sql);
                    if($_sql=='select'){
                        $tag=0;
                        $tab=0;
                        $as=0;
                        $emm=0;
                        continue;
                    }
                    if($tag==1){
                        if($as){
                            $as=0;
                            continue;
                        }
                        if($_sql=='as'){
                            $as=1;
                            $tab=0;
                            $emm=0;
                            continue;
                        }
                        if ($_sql=='join'){
                            $tab=0;
                            $emm=0;
                            continue;
                        }
                        if($emm){
                            continue;
                        }
                        if($_sql=='on'){
                            $emm=1;
                            continue;
                        }
                        if($tab==0){
                            $table[]=$this->_table($sql);
                            $tab=1;
                            $emm=0;
                            continue;
                        }else $tag=0;
                    }
                    if(substr($_sql, 0,1)=='"'){
                        if(substr($_sql, -1)=='"'){
                            continue;
                        }
                        $tag=-1;
                        continue;
                    }
                    if(substr($_sql,0 ,1)=="'"){
                        if(substr($_sql, -1)=="'"){
                            continue;
                        }
                        $tag=-2;
                        continue;
                    }
                    if($tag==0&&in_array($_sql,['from'])){
                        $tag=1;
                        $emm=0;
                        continue;
                    }
                    if($tag==-1&&substr($_sql, -1)=='"'){
                        $tag=0;
                        continue;
                    }
                    if($tag==-2&&substr($_sql, -1)=="'"){
                        $tag=0;
                        continue;
                    }
                }
            break;
            case 'desc':
                while (($sql=array_shift($sqls))!==null){
                    $sql=trim($sql);
                    if(empty($sql))continue;
                    $table[]=$this->_table($sql);
                }
            break;
            case 'show':
                $ta=0;
                while (($sql=array_shift($sqls))!==null){
                    $sql=trim($sql);
                    if(empty($sql))continue;
                    if ($sql=='table'){
                        $ta=1;
                        continue;
                    }
                    if($ta){
                        $table[]=$this->_table($sql);
                        break;
                    }
                }
            break;
        }
        return array_unique($table);
    }
    /**
     * @return array $table
     */
    public function execParseTable($sql){
        $table=[];
        $sqls=preg_split('/\s|=|\(|\)|,/', trim($sql));
        switch (strtolower(array_shift($sqls))){
            case 'create':
                $tb=0;
                while (($sql=array_shift($sqls))!==null){
                    $sql=trim($sql);
                    if(empty($sql))continue;
                    $_sql=strtolower($sql);
                    if ($_sql=='table'){
                        $tb=1;
                        continue;
                    }
                    if($tb){
                       $table[]=$this->_table($sql);
                       break;
                    }
                }
            break;
            case 'insert':
                $into=0;
                $de=0;
                while (($sql=array_shift($sqls))!==null){
                    $sql=trim($sql);
                    if(empty($sql))continue;
                    $_sql=strtolower($sql);
                    if($into==1){
                        if($de&&$_sql=='into'){
                            $de=0;continue;
                        }
                        $table[]=$this->_table($_sql);
                        break;
                    }
                    if($into==0&&$_sql=='into'){
                        $into=1;
                        continue;
                    }
                    if($into==0&&$_sql=='delayed'){
                        $into=1;
                        $de=1;
                        continue;
                    }
                }
                break;
            case 'delete':
                $det=0;
                $emm=0;
                $as=0;
                while (($sql=array_shift($sqls))!==null){
                    $sql=trim($sql);
                    if(empty($sql))continue;
                    $_sql=strtolower($sql);
                    if($det==1){
                        if($_sql=='where'){
                            break;
                        }
                        if($emm){
                            continue;
                        }
                        if($_sql=='on'){
                            $emm=1;
                            continue;
                        }
                        if($as){
                            $as=0;
                            continue;
                        }
                        if($_sql=='as'){
                            $as=1;
                            continue;
                        }
                        if(in_array($_sql, ['join'])){
                            $emm=0;
                            continue;
                        }
                        $table[]=$this->_table($sql);
                        continue;
                    }
                    if ($_sql=='from'){
                        $det=1;continue;
                    }
                }
                break;
            case 'update':
//                 UPDATE sss.yaf_aaa1 join sss.yaf_aaa on sss.yaf_aaa1.id =sss.yaf_aaa.id
//                 SET sss.yaf_aaa1.goodsId=1,sss.yaf_aaa.goodsId=2
//                 WHERE sss.yaf_aaa.id=1;
                $ups=1;
                $emm=0;
                $as=0;
                while (($sql=array_shift($sqls))!==null){
                    $sql=trim($sql);
                    if(empty($sql))continue;
                    if(!$ups)break;
                    $_sql=strtolower($sql);
                    if($ups==1){
                        if($_sql=='set'){
                            break;
                        }
                        if($emm){
                            continue;
                        }
                        if($_sql=='on'){
                            $emm=1;
                            continue;
                        }
                        if ($as){
                            $as=0;
                            continue;
                        }
                        if($_sql=='as'){
                            $as=1;
                            continue;
                        }
                        if(in_array($_sql, ['join'])){
                            $emm=0;
                            continue;
                        }
                        $table[]=$this->_table($sql);
                        continue;
                    }
                }
            break;
        }
        return array_unique($table);
    }
}
