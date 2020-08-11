<?php

require('../public/infobip/api/model/sms/mt/send/SMSResponse.php');
require('../public/infobip/api/AbstractApiClient.php');
require('../public/infobip/api/model/sms/mt/send/textual/SMSTextualRequest.php');

/**
 * This is a generated class and is not intended for modification!
 */
class SendSingleTextualSms extends AbstractApiClient {

    public function __construct($configuration) {
        parent::__construct($configuration);
    }

    /**
     * @param SMSTextualRequest $body
     * @return SMSResponse
     */
    public function execute(SMSTextualRequest $body) {
        $restPath = $this->getRestUrl("/sms/1/text/single");
        $content = $this->executePOST($restPath, null, $body);
        return json_encode($content);//$this->map(json_decode($content), get_class(new SMSResponse));
    }

}
