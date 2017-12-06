# proportional-proof-of-work-laravel
PHP Laravel Composer library for proof of work computation and associated middleware to throttle requests (WIP)

The concept really is a library that can perform as a combination of CSRF and Throttle. A page or endpoint protected by the PPOW middleware requires the client to perform some work- the intensity requirement would be determined by the number of accesses recently and the baseline value of the resource. The client computes a hash collision, which takes time, and essentially self-throttles as the work requirement increases, making scraping and automation impractical. A normally behaving client would never notice the minimal amount of work required.

The author is presently investigating two approaches- one which works as part of form submission and another which can protect GET assets with a knowledeable client (site-specific JavaScript code).    


Laravel (WIP): https://github.com/jessica-mulein/proportional-proof-of-work-laravel / https://packagist.org/packages/jessica-mulein/proportional-proof-of-work-laravel

Laravel Integration Demo (WIP): https://github.com/jessica-mulein/proportional-proof-of-work-laravel-demo

Fontend (WIP): https://github.com/jessica-mulein/proportional-proof-of-work-js
