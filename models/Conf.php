<?php

namespace app\models;

use app\components\GlobalHelper;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "conf".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $name
 * @property integer $level
 * @property integer $status
 * @property string $version
 * @property integer $created_at
 * @property string $deploy_from
 * @property string $excludes
 * @property string $release_user
 * @property string $release_to
 * @property string $release_library
 * @property string $hosts
 * @property string $pre_deploy
 * @property string $post_deploy
 * @property string $post_release
 * @property string $git_type
 * @property integer $audit
 */
class Conf extends \yii\db\ActiveRecord
{

    // 有效状态
    const STATUS_VALID = 1;

    // 测试环境
    const LEVEL_TEST  = 1;

    // 仿真环境
    const LEVEL_SIMU  = 2;

    // 线上环境
    const LEVEL_PROD  = 3;

    const AUDIT_YES = 1;

    const AUDIT_NO = 2;

    const GIT_BRANCH = 'branch';

    const GIT_TAG = 'tag';

    public static $CONF;

    public static $LEVEL = [
        self::LEVEL_TEST => 'test',
        self::LEVEL_SIMU => 'simu',
        self::LEVEL_PROD => 'prod',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'conf';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'git_url', 'name', 'level', 'deploy_from', 'release_user', 'release_to', 'release_library', 'hosts'], 'required'],
            [['user_id', 'level', 'status', 'audit'], 'integer'],
            [['name', 'version'], 'string', 'max' => 20],
            [['git_url', 'deploy_from', 'release_to', 'release_library'], 'string', 'max' => 200],
            [['excludes', 'pre_deploy', 'post_deploy', 'post_release', 'hosts'], 'string', 'max' => 500],
            [['release_user', 'git_type'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'              => 'ID',
            'user_id'         => 'User ID',
            'name'            => 'Name',
            'level'           => 'Level',
            'status'          => 'Status',
            'version'         => 'Version',
            'created_at'      => 'Created At',
            'deploy_from'     => 'Deploy From',
            'excludes'        => 'Excludes',
            'release_user'    => 'Release User',
            'release_to'      => 'Release To',
            'release_library' => 'Release Library',
            'hosts'           => 'Hosts',
            'pre_deploy'      => 'Pre Deploy',
            'post_deploy'     => 'Post Deploy',
            'post_release'    => 'Post Release',
            'git_type'        => 'Git Type',
            'audit'           => 'Audit',
        ];
    }

    /**
     * 获取当前进程的项目配置
     *
     * @param $id
     * @return string|\yii\db\ActiveQuery
     */
    public static function getConf($id = null) {
        if (empty(static::$CONF)) {
            static::$CONF = static::findOne($id);
        }
        return static::$CONF;
    }

    /**
     * 根据git地址获取项目名字
     *
     * @param $gitUrl
     * @return mixed
     */
    public static function getGitProjectName($gitUrl) {
        if (preg_match('#.*/(.*?)\.git#', $gitUrl, $match)) {
            return $match[1];
        }

        return $gitUrl;
    }

    /**
     * 拼接宿主机的仓库目录
     * {deploy_from}/{env}/{project}
     *
     * @return string
     */
    public static function getDeployFromDir() {
        $from    = static::$CONF->deploy_from;
        $env     = isset(Conf::$LEVEL[static::$CONF->level]) ? Conf::$LEVEL[static::$CONF->level] : 'unknow';
        $project = Conf::getGitProjectName(static::$CONF->git_url);

        return sprintf("%s/%s/%s", rtrim($from, '/'), rtrim($env, '/'), $project);
    }

    /**
     * 拼接目标机要发布的目录
     * {release_library}/{project}/{version}
     *
     * @param $version
     * @return string
     */
    public static function getReleaseVersionDir($version) {
        return sprintf('%s/%s/%s', rtrim(static::$CONF->release_library, '/'),
            Conf::getGitProjectName(static::$CONF->git_url), $version);
    }

    /**
     * 获取当前进程配置的目标机器host列表
     */
    public static function getHosts() {
        return GlobalHelper::str2arr(static::$CONF->hosts);
    }

}