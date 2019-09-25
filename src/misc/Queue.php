
use SplQueue;

Private $queue;
        $this->queue = new SplDoublyLinkedList;

 Public function performData($data = null)
 {
     if ($data !== null) {
         $this->queue->push();
     } else {
         try {
             if (call_user_func([$this, 'encode'], $stored = $this->queue->pop()) === 0)
                 return $stored['data'];

             return null;
         } catch (RuntimeException $e) {
             $this->lastErrorString = $e;
             return null;
         }
     }

 }
