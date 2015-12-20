<?php
/**
 * Table Definition for UserSettings
 *
 * PHP version 5
 *
 * Copyright (C) Moravian Library 2015.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Db_Table
 * @author   Martin Kravec <martin.kravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-3.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace CPK\Db\Table;

use VuFind\Db\Table\Gateway,
    VuFind\Db\Row\User,
    Zend\Config\Config,
    Zend\Db\Sql\Update,
    Zend\Db\Sql\Select;

/**
 * Table Definition for UserSettings
 *
 * @category VuFind2
 * @package  Db_Table
 * @author   Martin Kravec <martin.kravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-3.0.php GNU General Public License
 * @link     http://vufind.org Main Site
 */
class UserSettings extends Gateway
{
    /**
     * @var \Zend\Config\Config
     */
    protected $config;
    
    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     * 
     * @return void 
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        parent::__construct('user_settings', 'CPK\Db\Row\UserSettings');
    }

    /**
     * Returns array of user settings from user_settings table
     *
     * @return array
     */
    public function getSettings(User $user)
    {       
        return $this->select(['user_id' => $user['id']])->toArray();
    }
    
    /**
     * Returns array of user settings from user_settings table
     *
     * @return string|null
     */
    public function getUserCitationStyle(User $user)
    {
        $select = new Select($this->table);
        $select->limit(1);
        
        $condition = 'user_id="'.$user['id'].'" AND citation_style IS NOT NULL';
        $predicate = new \Zend\Db\Sql\Predicate\Expression($condition);
        $select->where($predicate);
        
        $result = $this->executeAnyZendSQLSelect($select)->current();
        return $result;
    }
    
    /**
     * Set preferred citation style into user_settings table
     * 
     * @param VuFind\Db\Row\User $user
     * @param string $citationStyleValue
     *
     * @return void
     */
    public function setCitationStyle(User $user, $citationStyleValue)
    {
        $preferredCitationStyle = $this->getUserCitationStyle($user);
        
        // insert new setting if not already set
        if ($preferredCitationStyle === false) {
            
            $this->getDbConnection()->beginTransaction();
            $this->getDbTable($this->table)->insert([
                'user_id' => $user->id,
                'citation_style' => $citationStyleValue
            ]);
            $this->getDbConnection()->commit();
            
        } else { // update setting if already set
            
            if ($citationStyleValue === $preferredCitationStyle['value'])
                return; // do not update the same value
        
            $update = new Update($this->table);
            $update->set([
                'citation_style' => $citationStyleValue
            ]);
            $update->where([
                'user_id' => $user->id
            ]);
            
            $this->getDbConnection()->beginTransaction();
            $this->sql->prepareStatementForSqlObject($update)->execute();
            $this->getDbConnection()->commit();
        }
    }
    
    /**
     * Executes any Select
     *
     * @param Zend\Db\Sql\Select $select
     *
     * @return Zend\Db\Adapter\Driver\ResultInterface $result
     */
    protected function executeAnyZendSQLSelect(Select $select)
    {
        $statement = $this->sql->prepareStatementForSqlObject($select);
        return $statement->execute();
    }
    
    /**
     * Executes any Update
     *
     * @param Zend\Db\Sql\Update $update
     *
     * @return Zend\Db\Adapter\Driver\ResultInterface $result
     */
    protected function executeAnyZendSQLUpdate(Update $update)
    {
        $statement = $this->sql->prepareStatementForSqlObject($update);
        return $statement->execute();
    }
    
    /**
     * Returns database connection
     *
     * @return \Zend\Db\Adapter\Driver\Mysqli\Connection
     */
    protected function getDbConnection()
    {
        return $this->getAdapter()->driver->getConnection();
    }
}