<?php
/**
 * app æç°å.
 *
 * å¼æ¨¡å
 * bs.
 */
use Ts\Models as Model;

class ApplicationApi extends Api
{
    //å å¯key
    protected $key = 'ThinkSNS';

    //æ°æ®ç»ä¸è¿åæ ¼å¼
    private function rd($data = '', $msg = 'ok', $status = 0)
    {
        return array(
            'data'   => $data,
            'msg'    => $msg,
            'status' => $status,
        );
    }

    //è·åçæ¬å· ç¨äºappè·åæ´æ°éç½®
    public function getVersion()
    {
        $info = model('Xdata')->get('admin_Application:ZB_config');
        if (!empty($info['version'])) {
            $version = $info['version'];
        } else {
            $version = 1; //æªéç½®  åå§çæ¬
        }

        return $this->rd($version);
    }

    //è·åæ¯ä»ç¸å³éç½®
    public function getZBConfig()
    {
        //è·åéç½®ç®åå å¯
        $key = $this->data['key'];
        if (md5($this->key) != $key) {
            return $this->rd('', 'è®¤è¯å¤±è´¥', 1);
        }
        $chongzhi_info = model('Xdata')->get('admin_Config:charge');
        $info['weixin'] = in_array('weixin', $chongzhi_info['charge_platform']) ? true : false;
        $info['alipay'] = in_array('alipay', $chongzhi_info['charge_platform']) ? true : false;
        $info['cash_exchange_ratio_list'] = getExchangeConfig('cash');
        $info['charge_ratio'] = $chongzhi_info['charge_ratio'] ?: '100'; //1äººæ°å¸ç­äºå¤å°ç§¯å
        $info['charge_description'] = $chongzhi_info['description'] ?: 'åå¼æè¿°'; //åå¼æè¿°
        $field = $this->data['field']; //å³é®å­  ä¸ä¼ ä¸ºå¨é¨
        if ($field) {
            $field = explode(',', $field);
            foreach ($info as $key => $value) {
                if (!in_array($key, $field)) {
                    unset($info[$key]);
                }
            }
        }

        return $this->rd($info);
    }

    //çææç°è®¢åå·
    private function getOrderId()
    {
        //æç¨è¿ç§ç®åçè®¢åå·çæåæ³ããããè¯·æ±å¯éæ¶å¯è½åºç°è®¢åå·éå¤ï¼
        $number = date('YmdHis').rand(1000, 9999);

        return $number;
    }

    /**
     * åå¸æç°ç³è¯·.
     */
    public function createOrder()
    {
        $data['order_number'] = $this->getOrderId();
        $data['uid'] = $this->mid;

        $accountinfo = $this->getUserAccount();
        if ($accountinfo['status'] == 1) {
            return $this->rd('', 'è¯·åç»å®æç°è´¦æ·', 1);
        }
        $data['account'] = $accountinfo['data']['account'];
        $data['type'] = intval($accountinfo['data']['type']); //ç»å®è·å

        $data['gold'] = intval($this->data['gold']);
        $data['amount'] = $this->data['amount'];
        $data['ctime'] = time();
        // if (!$data['account']) {

        //     return $this->rd('','è¯·å¡«åæç°è´¦æ·',1);
        // }
        if (!$data['gold']) {
            return $this->rd('', 'è¯·å¡«åæç°éé¢', 1);
        }
        $score = D('credit_user')->where(array('uid' => $this->mid))->getField('score');
        if ($score < $data['gold']) {
            return $this->rd('', 'ç§¯åä¸è¶³', 1);
        }
        $info = Model\CreditOrder::insert($data);
        if ($info) {
            $record['cid'] = 0; //æ²¡æå¯¹åºçç§¯åè§å
            $record['type'] = 4; //4-æç°
            $record['uid'] = $this->mid;
            $record['action'] = 'ç¨æ·æç°';
            $record['des'] = '';
            $record['change'] = 'ç§¯å<font color="green">-'.$data['gold'].'</font>'; //æç°ç³è¯·æ£ç§¯å   å¦æé©³ååå åæ¥
            $record['ctime'] = time();
            $record['detail'] = json_encode(array('score' => '-'.$data['gold']));
            D('credit_record')->add($record);
            D('credit_user')->setDec('score', 'uid='.$this->mid, $data['gold']);
            D('Credit')->cleanCache($this->mid);

            return $this->rd('', 'æäº¤æåè¯·ç­å¾å®¡æ ¸', 0);
        } else {
            return $this->rd('', 'ä¿å­å¤±è´¥ï¼è¯·ç¨ååè¯', 1);
        }
    }

    /**
     * ç»å®/è§£ç»è´¦æ·
     * bs.
     */
    public function setUserAccount()
    {
        $status = intval($this->data['status']) ?: 1; //type 1-ç»å® 2-è§£ç»
        if ($status == 1) {
            $data['account'] = $this->data['account'];
            if (!$data['account']) {
                return $this->rd('', 'è¯·è¾å¥éè¦ç»å®çè´¦æ·', 1);
            }
            $data['type'] = intval($this->data['type']) ?: 1; //1-æ¯ä»å® 2-å¾®ä¿¡
            if (Model\UserAccount::find($this->mid)) {
                return $this->rd('', 'å·²æç»å®è´¦æ·', 1);
            }
            $data['uid'] = $this->mid;
            $data['ctime'] = time();
            $info = Model\UserAccount::insert($data);
            if ($info) {
                return $this->rd('', 'ç»å®æå', 0);
            } else {
                return $this->rd('', 'ç»å®å¤±è´¥ï¼è¯·ç¨ååè¯', 1);
            }
        } else {
            if (!Model\UserAccount::find($this->mid)) {
                return $this->rd('', 'æªç»å®è´¦æ·', 1);
            }
            $info = Model\UserAccount::where('uid', $this->mid)->delete();
            if ($info) {
                return $this->rd('', 'è§£ç»æå', 0);
            } else {
                return $this->rd('', 'æä½å¤±è´¥ï¼è¯·ç¨ååè¯', 1);
            }
        }
    }

    /**
     * æ¥çæç°è´¦æ·.
     */
    public function getUserAccount()
    {
        $info = Model\UserAccount::find($this->mid);
        if (!$info) {
            return $this->rd('', 'æªç»å®è´¦æ·', 1);
        } else {
            $data['account'] = $info->account;
            $data['type'] = $info->type;

            return $this->rd($data);
        }
    }

    public function test()
    {
        $order = $this->getOrderId();

        return $order;
    }
}
