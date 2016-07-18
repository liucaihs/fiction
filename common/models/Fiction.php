<?php

namespace common\models;

use Overtrue\Pinyin\Pinyin;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%fiction}}".
 *
 * @property integer $id
 * @property string $categoryKey
 * @property string $fictionKey
 * @property string $ditchKey
 * @property string $name
 * @property string $description
 * @property string $author
 * @property string $url
 * @property integer $status
 */
class Fiction extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%fiction}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['description'], 'string'],
            [['status'], 'integer'],
            [['categoryKey', 'ditchKey'], 'string', 'max' => 80],
            [['fictionKey'], 'string', 'max' => 100],
            [['name', 'author', 'url'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'categoryKey' => 'Category Key',
            'fictionKey' => 'Fiction Key',
            'ditchKey' => 'Ditch Key',
            'name' => 'Name',
            'description' => 'Description',
            'author' => 'Author',
            'url' => 'Url',
            'status' => 'Status',
        ];
    }

    /**
     * 更新所有分类的小说信息
     */
    public static function updateCategoryFictionList()
    {
        //获取所有分类
        $categories = Category::find()->all();
        $pinyin = new Pinyin();
        foreach ($categories as $category) {
            $ditchKey = $category->ditchKey;
            $categoryKey = $category->categoryKey;
            $url = $category->url;
            $categoryRule = $category->categoryRule;
            $categoryNum = $category->categoryNum;
            $fictionRule = $category->fictionRule;
            $fictionLinkType = $category->fictionLinkType;
            if ($ditchKey && $categoryKey && $url && $categoryRule && $fictionRule) {
                //根据小说链接类型 获取小说链接地址的相对地址
                if ($fictionLinkType === 'home') {
                    $ditch = $category->ditch;
                    if (!$ditch) {
                        //todo 记录日志 未找到指定小说的渠道
                        continue;
                    }
                    $refUrl = $ditch->url;
                } elseif ($fictionLinkType === 'current') {
                    $refUrl = $url;
                } else {
                    $refUrl = '';
                }
                $fictionList = Gather::gatherCategoryFictionList($url, $categoryRule, $fictionRule, $categoryNum, $refUrl);
                if ($fictionList) {
                    foreach ($fictionList as $v) {
                       if ($v['url'] && $v['text']) {
                           $fictionKey = implode($pinyin->convert($v['text']));
                           $fiction = Fiction::find()->where(['ditchKey' => $ditchKey, 'categoryKey' => $category, 'fictionKey' => $fictionKey])->one();
                           if (null === $fiction) {
                               $fiction = new Fiction();
                               $fiction->ditchKey = $ditchKey;
                               $fiction->categoryKey = $categoryKey;
                               $fiction->fictionKey = $fictionKey;
                               $fiction->status = 1;
                           }
                           $fiction->url = $url;
                           $fiction->name = $v['text'];
                           $res = $fiction->save();
                           if (!$res){
                               //todo 添加日志 更新小说信息失败
                           }
                       }
                    }
                }
            } else {
                //todo 记录日志 分类缺少必要信息
            }
        }
    }
}
