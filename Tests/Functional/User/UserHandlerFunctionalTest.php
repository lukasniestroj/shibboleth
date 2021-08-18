<?php
/**
 * Created by PhpStorm.
 * User: tschikarski
 * Date: 10.07.17
 * Time: 16:50
 */

namespace TrustCnct\Shibboleth\User;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserHandlerFunctionalTest extends \Nimut\TestingFramework\TestCase\FunctionalTestCase
{
    protected $userHandler;
    protected $db_user;
    protected $db_group;

    protected function setUp() {
        parent::setUp();
        global $TYPO3_CONF_VARS;
        $TYPO3_CONF_VARS['EXTENSIONS']['shibboleth'] = [
            "mappingConfigPath" => "\/typo3conf\/ext\/shibboleth\/Tests\/Functional\/Fixtures\/config.txt",
            "sessions_handlerURL" => "Shibboleth.sso",
            "sessionInitiator_Location" => "\/Login",
            "FE_enable" => "1",
            "FE_autoImport" => "1",
            "FE_autoImport_pid" => "2",
            "BE_enable" => "0",
            "BE_autoImport" => "1",
            "BE_autoImportDisableUser" => "0",
            "BE_loginTemplatePath" => "typo3conf\/ext\/shibboleth\/res\/be_form\/login7.html",
            "BE_logoutRedirectUrl" => "\/typo3conf\/ext\/shibboleth\/res\/be_form\/logout.html",
            "BE_disabledUserRedirectUrl" => "\/typo3conf\/ext\/shibboleth\/res\/be_form\/nologinyet.html",
            "enableAlwaysFetchUser" => "1",
            "entityID" => "",
            "forceSSL" => "1",
            "FE_applicationID" => "",
            "BE_applicationID" => "",
            "FE_devLog" => "1",
            "BE_devLog" => "0",
            "database_devLog" => "0"
        ];
        $this->db_user = [
            'table' => 'fe_users',
            'userid_column' => 'uid',
            'username_column' => 'username',
            'userident_column' => 'password',
            'usergroup_column' => 'usergroup',
            'enable_clause' => '',
            'checkPidList' => 0,
            'check_pid_clause' => '`pid` IN (2)'
        ];
        $this->db_group = [
            'table' => 'fe_groups'
        ];
        $this->importDataSet('web/typo3conf/ext/shibboleth/Tests/Functional/Fixtures/fe_users.xml');
        $this->importDataSet('web/typo3conf/ext/shibboleth/Tests/Functional/Fixtures/be_users.xml');
    }

    /**
     * @test
     */
    public function constructorForFrontendCaseTest()
    {
        /** @var UserHandler $userHandler */
        $userHandler = GeneralUtility::makeInstance(UserHandler::class,'FE','fe_users','fe_groups','Shib_Session_ID',false,'');
        $this->assertFalse($userHandler->tsfeDetected);
    }

    /**
     * @test
     */
    public function getMappingConfigPathTest() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock('TrustCnct\Shibboleth\User\UserHandler',['getEnvironmentVariable'], ['FE','fe_users','fe_groups','Shib_Session_ID'],'',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        $userHandler->_callRef('__construct', $loginType, $db_user, $db_group, $shibbSessionIdKey);
        $expectedPath = $_SERVER['TYPO3_PATH_ROOT'].'/typo3conf/ext/shibboleth/Tests/Functional/Fixtures/config.txt';
        $this->assertSame($expectedPath,$userHandler->mappingConfigAbsolutePath);
    }

    /**
     * @test
     */
    public function mockGetTyposcriptConfigurationTest() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
                'FE',
                'fe_users',
                'fe_groups',
                'Shib_Session_ID',
                false,
                ''),
        '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        $userHandler->_callRef('__construct', $loginType, $db_user, $db_group, $shibbSessionIdKey);
        $this->assertSame('TEXT',$userHandler->config['IDMapping.']['shibID']);
    }

    /**
     * @test
     */
    public function typo3IdFieldFromConfigFileTest() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        $userHandler->_callRef('__construct', $loginType, $db_user, $db_group, $shibbSessionIdKey);
        $idField = $userHandler->config['IDMapping.']['typo3Field'];
        $this->assertSame('username',$idField);
    }

    /**
     * @test
     */
    public function lookUpShibbolethUserInDatabaseReportsErrorOnEmptyIdValue() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = '';
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        $userHandler->_callRef('__construct', $loginType, $db_user, $db_group, $shibbSessionIdKey);
        $this->assertSame('Shibboleth data evaluates username to empty string!', $userHandler->lookUpShibbolethUserInDatabase());

    }

    /**
     * @test
     */
    public function lookUpShibbolethUserInDatabaseReturnsExistingFeUser() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'myself@testshib.org';
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userFromDB = $userHandler->lookUpShibbolethUserInDatabase();
        $this->assertTrue(is_array($userFromDB),'Expected array');
        $this->assertArrayHasKey('uid', $userFromDB);
        $this->assertSame(2, (int) $userFromDB['uid']);
        $this->assertStringStartsWith('myself', $userFromDB['username']);

    }

    /**
     * @test
     */
    public function lookUpShibbolethUserInDatabaseReturnsDisabledFeUser() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'disabled@testshib.org';
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userFromDB = $userHandler->lookUpShibbolethUserInDatabase();
        $this->assertTrue(is_array($userFromDB),'Expected array');
        $this->assertArrayHasKey('uid', $userFromDB);
        $this->assertSame(4, (int) $userFromDB['uid']);
        $this->assertStringStartsWith('disabled', $userFromDB['username']);

    }

    /**
     * @test
     */
    public function lookUpShibbolethUserInDatabaseReturnsNullIfFeUserDoesNotExist() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'false@testshib.org';
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        /** @var UserHandler $userHandler */
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userFromDB = $userHandler->lookUpShibbolethUserInDatabase();
        $this->assertFalse(is_array($userFromDB),'Did not expect array');
        $this->assertEmpty($userFromDB);

    }

    /**
     * @test
     */
    public function lookUpShibbolethUserInDatabaseReturnsNullIfFeUserIsDeleted() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'deleted@testshib.org';
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        /** @var UserHandler $userHandler */
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userFromDB = $userHandler->lookUpShibbolethUserInDatabase();
        $this->assertFalse(is_array($userFromDB),'Did not expect array');
        $this->assertEmpty($userFromDB);

    }

    /**
     * @test
     */
    public function lookUpShibbolethUserInDatabaseReturnsNullOnPidMismatchFe() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'wrongpid@testshib.org';
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        /** @var UserHandler $userHandler */
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userFromDB = $userHandler->lookUpShibbolethUserInDatabase();
        $this->assertFalse(is_array($userFromDB),'Did not expect array');
        $this->assertEmpty($userFromDB);

    }

    /**
     * @test
     */
    public function existingFeUserIsUpdatedCorrectly() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'myself@testshib.org';
        $_SERVER['affiliation'] = 'goes to company';
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        /** @var UserHandler $userHandler */
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userBefore = $userHandler->lookUpShibbolethUserInDatabase();
        $userBefore = $userHandler->transferShibbolethAttributesToUserArray($userBefore);
        unset($userBefore['_allowUser']);
        unset($userBefore['tx_shibboleth_shibbolethsessionid']);
        $uidBefore = $userBefore['uid'];
        $this->assertSame(2, (int) $uidBefore);
        $uidReported = $userHandler->synchronizeUserData($userBefore);
        $this->assertSame(2, (int) $uidReported);
        $userAfter = $userHandler->lookUpShibbolethUserInDatabase();
        $this->assertSame('goes to company', $userAfter['company']);
        $this->assertSame('first time set', $userAfter['fax']);

    }
    /**
     * @test
     */
    public function existingFeUserUpdateFailsOnUnknownField() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'myself@testshib.org';
        $_SERVER['affiliation'] = 'goes to company';
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        /** @var UserHandler $userHandler */
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userBefore = $userHandler->lookUpShibbolethUserInDatabase();
        $userBefore = $userHandler->transferShibbolethAttributesToUserArray($userBefore);
        unset($userBefore['_allowUser']);
        unset($userBefore['tx_shibboleth_shibbolethsessionid']);
        $userBefore['nonExistingField'] = 'dummy';
        $uidReported = $userHandler->synchronizeUserData($userBefore);
        $this->assertSame(0, (int) $uidReported);

    }

    /**
     * @test
     */
    public function nonExistingFeUserIsInsertedCorrectly() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'new@testshib.org';
        $_SERVER['affiliation'] = 'goes to company';
        $_SERVER['entitlement'] = 'first time set';
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        /** @var UserHandler $userHandler */
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userBefore = $userHandler->transferShibbolethAttributesToUserArray(NULL);
        unset($userBefore['_allowUser']);
        unset($userBefore['tx_shibboleth_shibbolethsessionid']);
        $uidReported = $userHandler->synchronizeUserData($userBefore);
        $this->assertSame(6, (int) $uidReported);
        $userAfter = $userHandler->lookUpShibbolethUserInDatabase();
        $this->assertSame('goes to company', $userAfter['company']);
        $this->assertSame('first time set', $userAfter['fax']);

    }

    /**
     * @test
     */
    public function nonExistingFeUserInsertFailsOnUnknownField() {
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'FE',
            'fe_users',
            'fe_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'new@testshib.org';
        $_SERVER['affiliation'] = 'goes to company';
        $_SERVER['entitlement'] = 'first time set';
        $loginType = 'FE';
        $db_user = 'fe_users';
        $db_group = 'fe_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        /** @var UserHandler $userHandler */
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userBefore = $userHandler->transferShibbolethAttributesToUserArray(NULL);
        unset($userBefore['_allowUser']);
        unset($userBefore['tx_shibboleth_shibbolethsessionid']);
        $userBefore['nonExistingField'] = 'dummy';
        $uidReported = $userHandler->synchronizeUserData($userBefore);
        $this->assertSame(0, (int) $uidReported);

    }

    /**
     * @test
     */
    public function lookUpShibbolethBeUserInDatabaseReturnsNullOnNonZeroPid() {
        $this->db_user = array(
            'table' => 'be_users',
            'userid_column' => 'uid',
            'username_column' => 'username',
            'userident_column' => 'password',
            'usergroup_column' => 'usergroup',
            'enable_clause' => ''
        );
        $this->db_group = array(
            'table' => 'be_groups'
        );
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'BE',
            'be_users',
            'be_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'wrongpid@testshib.org';
        $loginType = 'BE';
        $db_user = 'be_users';
        $db_group = 'be_groups';
        $shibbSessionIdKey = 'Shib_Session_ID';
        /** @var UserHandler $userHandler */
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userFromDB = $userHandler->lookUpShibbolethUserInDatabase();
        $this->assertFalse(is_array($userFromDB),'Did not expect array');
        $this->assertEmpty($userFromDB);

    }

    /**
     * @test
     */
    public function lookUpShibbolethUserInDatabaseReturnsExistingBeUser() {
        $this->db_user = array(
            'table' => 'be_users',
            'userid_column' => 'uid',
            'username_column' => 'username',
            'userident_column' => 'password',
            'usergroup_column' => 'usergroup',
            'enable_clause' => ''
        );
        $this->db_group = array(
            'table' => 'be_groups'
        );
        /** @var UserHandler $userHandler */
        $userHandler = $this->getAccessibleMock(\TrustCnct\Shibboleth\User\UserHandler::class,['getEnvironmentVariable'],array(
            // $loginType, $db_user, $db_group, $shibSessionIdKey, $writeDevLog = FALSE, $envShibPrefix = ''
            'BE',
            'be_users',
            'be_groups',
            'Shib_Session_ID',
            false,
            ''),
            '',false);
        $userHandler->expects($this->once())->method('getEnvironmentVariable')->will($this->returnValue($_SERVER['TYPO3_PATH_ROOT']));
        $_SERVER['eppn'] = 'myself@testshib.org';
        $loginType = 'BE';
        $shibbSessionIdKey = 'Shib_Session_ID';
        $userHandler->_callRef('__construct', $loginType, $this->db_user, $this->db_group, $shibbSessionIdKey);
        $userFromDB = $userHandler->lookUpShibbolethUserInDatabase();
        $this->assertTrue(is_array($userFromDB),'Expected array');
        $this->assertArrayHasKey('uid', $userFromDB);
        $this->assertSame(1, (int) $userFromDB['uid']);
        $this->assertStringStartsWith('myself', $userFromDB['username']);

    }

}
