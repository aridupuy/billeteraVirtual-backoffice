<?php

require('../public/infobip/api/model/sms/mt/send/SMSResponse.php');
require('../public/infobip/api/AbstractApiClient.php');
require('../public/infobip/api/model/sms/mt/send/textual/SMSAdvancedTextualRequest.php');

/**
 * This is a generated class and is not intended for modification!
 */
class SendMultipleTextualSmsAdvanced extends AbstractApiClient {

    public function __construct($configuration) {
        parent::__construct($configuration);
    }

    /**
     * @param SMSAdvancedTextualRequest $body
     * @return SMSResponse
     */
    public function execute(SMSAdvancedTextualRequest $body) {
        $restPath = $this->getRestUrl("/sms/1/text/advanced");
        $content = $this->executePOST($restPath, null, $body);
        return $this->map(json_decode($content), get_class(new SMSResponse));
    }

}