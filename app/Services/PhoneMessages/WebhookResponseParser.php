<?php
namespace App\Services\PhoneMessages;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use Exception;
use Illuminate\Support\Facades\Log;

class WebhookResponseParser
{
    protected $rawData;
    protected $result;

    public function __construct($data)
    {
        $this->rawData = $data;
    }

    /**
     * Get refinded data.
     *
     * @return array of required fields.
     */
    public function get()
    {
        $this->refineData();

        return $this->result;
    }

    /**
     * Refind Raw Data and convert that data according to requirement.
     *
     * @return self.
     */
    public function refineData()
    {
        $data = [
            // 'to_country'        => $this->rawData['ToCountry'],
            // 'to_state'          => $this->rawData['ToState'],
            // 'to_city'           => $this->rawData['ToCity'],
            // 'to_zip'            => $this->rawData['ToZip'],
            // 'from_zip'          => $this->rawData['FromZip'],
            // 'from_state'        => $this->rawData['FromState'],
            // 'from_city'         => $this->rawData['FromCity'],
            // 'from_country'      => $this->rawData['FromCountry'],
            // 'sms_message_sid'   => $this->rawData['SmsMessageSid'],
            // 'sms_sid'           => $this->rawData['SmsSid'],
            'num_media'         => $this->rawData['NumMedia'],
            'status'            => $this->rawData['SmsStatus'],
            'message'           => $this->rawData['Body'],
            'to'                => $this->rawData['To'],
            'from'              => $this->rawData['From'],
            // 'num_segments'      => $this->rawData['NumSegments'],
            'sid'               => $this->rawData['SmsSid'],
            // 'media_content_type'=> $this->rawData['MediaContentType'],
            'media_urls'         => $this->setMedias(),
            // 'account_sid'       => $this->rawData['AccountSid'],
            // 'api_version'       => $this->rawData['ApiVersion'],
        ];

        $this->result = $data;

        return $this;
    }

    private function setMedias()
    {
        $mediaUrls = [];
        $totalFields = count($this->rawData);
        for($i = 0; $i < $totalFields; $i++) {
            if(!ine($this->rawData, 'MediaUrl'.$i)) {
                continue;
            }
            $filePath = $this->rawData['MediaUrl'.$i];
            $mimeType = $this->rawData['MediaContentType'.$i];
            $fileUrl = $this->getFile($filePath);
            if (!$fileUrl) {
                continue;
            }

            // $mediaUrls[] = addExtIfMissing($fileUrl, $mimeType);
            $mediaUrls[] = [
                'file' => $fileUrl,
                'mime_type' => $mimeType
            ];
        }
        return $mediaUrls;
    }

    /**
     * Create and send request and get response.
     *
     * @param String $url: string of url.
     * @param String $type: string of method types (get/post/put/delete).
     * @param Array $options: array of options/request params.
     * @return json response.
     */
    private function getFile($url)
    {
        try {
            $request = new GuzzleClient;
            $response = $request->get($url);
            return $response->getEffectiveUrl();
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $jsonBody = (string) $response->getBody();
            Log::error($jsonBody);
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}