<?php

namespace Insane\Journal\Observers;

use Insane\Journal\Models\Core\PaymentDocument;

class PaymentDocumentObserver
{

    public function saving(PaymentDocument $document)
    {
      $document->meta_data = $document->payable->prePaymentMeta($document);
    }


    public function saved(PaymentDocument $document)
    {
      // $metaData = $document->payable->postPaymentMeta($document);
      // $document->meta_data = array_merge(
      //   ...$document->meta_data,
      //   ...$metaData
      // );
      // $document->saveQuietly();
    }
}
