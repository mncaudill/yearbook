<?php

    date_default_timezone_set('America/Los_Angeles');
    
    // Leaving to go to NC
    print strtotime('2011/09/11 11:25 PM') . "\n";

    // Leaving NC
    date_default_timezone_set('America/New_York');
    print strtotime('Fri 09/16/2011 9:00 AM ') . "\n";

    date_default_timezone_set('America/Los_Angeles');

    // Leaving to go to NY
    print strtotime('2011/10/08 11:00 AM') . "\n";

    date_default_timezone_set('America/New_York');

    print strtotime('2011/10/14 2:00 PM') . "\n";

    date_default_timezone_set('America/Los_Angeles');
    
    // Leaving SF to NC for Christmas
    print strtotime('2011/12/22 8:00 AM') . "\n";

    // Leaving east coast to come back home
    date_default_timezone_set('America/New_York');

    print strtotime('2011/12/31 4:30 PM') . "\n";
