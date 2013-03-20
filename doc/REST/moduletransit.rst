##############
Transit API
##############

=====
info
=====

Parameters: none.

Sample *response* ::

    {
        "agencies": [
            {
                "id": "harvard",
                "title": "Harvard University Shuttles"
            },
            {
                "id": "mit",
                "title": "MIT Shuttles"
            },
            {
                "id": "mbta",
                "title": "MBTA Buses"
            }
        ],
        "sections": [
            {
                "key": "sysinfo",
                "title": "System Information",
                "items": [
                    {
                        "title": "About Harvard Shuttles",
                        "content": "<p>Some info about Harvard Shuttles</p>"
                    },
                    {
                        "title": "About MIT Shuttles",
                        "content": "Some info about the MIT Shuttles."
                    },
                    {
                        "title": "About MBTA Buses",
                        "content": "Some info about the MBTA Buses.<br/>Some more info."
                    },
                    {
                        "title": "Shuttles Calendar",
                        "content": "<h2>2010-&shy;2011 Shuttles Calendar</h2><h2>Full Service</h2>August 29-November 24<br/>November 28-December 21<br/>January 2-March 11<br/>March 20-May 14<br/><h2>No Service</h2>September 6<br/>October 11<br/>November 11<br/>November 25-27<br/>December 22-January 1<br/>March 12-19<br/>May 15<br/>"
                    }
                ]
            },
            {
                "key": "servicephone",
                "title": "Shuttle Service",
                "items": [
                    {
                        "title": "Shuttle Bus and Van Service",
                        "subtitle": "(617-495-0400)",
                        "url": "tel:+6174950400",
                        "class": "phone"
                    },
                    {
                        "title": "M2 Shuttle",
                        "subtitle": "(617-632-2800)",
                        "url": "tel:+6176322800",
                        "class": "phone"
                    }
                ]
            },
            {
                "key": "emergencyphone",
                "title": "Emergency Phone Numbers",
                "items": [
                    {
                        "title": "University Police",
                        "subtitle": "(617-495-1212)",
                        "url": "tel:+6174951212",
                        "class": "phone"
                    },
                    {
                        "title": "Health Services",
                        "subtitle": "(617-495-5711)",
                        "url": "tel:+6174955711",
                        "class": "phone"
                    }
                ]
            }
        ]
    }


======
stop
======

Parameters

* *id* (required) - stop ID, returned via `route`

Sample *response* ::

    {
        "id": "stop-0",
        "title": "Main Street at First Avenue",
        "coords": {
            "lat": 3.14159,
            "lon": -2.71828
        },
        "routes": [
            {
                "routeId": "abcdefg",
                "title": "Daytime Shuttle",
                "running": false,
                "arrives": [ ]
            },
            {
                "routeId": "hijklmn",
                "title": "Nighttime Shuttle",
                "running": true,
                "arrives": [ ]
            }
        ]
    }


======
routes
======

Parameters: none

Sample *response* ::

    [
        {

            "id": "abcdefg",
            "agency": "myAgency",
            "title": "Daytime Shuttle",
            "summary": "This shuttle runs from 5am to 8pm",
            "description": "",
            "color": "FFCC00",
            "frequency": 60,
            "running": false,
            "live": false,
            "view": "list"

        },
        {

            "id": "hijklmn",
            "agency": "myAgency",
            "title": "Nighttime Shuttle",
            "summary": "This shuttle runs from 8pm to 5am",
            "description": "",
            "color": "00CCFF",
            "frequency": 60,
            "running": true,
            "live": true,
            "view": "list"

        }
    ]


======
route
======

Parameters:

* *id* (required) - route ID, returned via `routes`

Sample *response* ::

    {
        "id": "abcdefg",
        "agency": "myAgency",
        "title": "Daytime Shuttle",
        "summary": "This shuttle runs from 5am to 8pm",
        "description": "",
        "color": "cc0000",
        "frequency": 60,
        "running": true,
        "live": true,
        "view": "list",
        "stopIconURL": "http://feed.com/stop.png",
        "vehicleIconURL": "http://feed.com/bus.png",
        "stops": [
            {
                "id": "stop-0",
                "routeId": "abcdefg",
                "title": "Main Street at First Avenue",
                "coords": {
                    "lat": 3.14159,
                    "lon": -2.71828
                },
                "arrives": [ ]
            },
            {
                "id": "stop-1",
                "routeId": "abcdefg",
                "title": "Main Street at Second Avenue",
                "coords": {
                    "lat": 3.14159,
                    "lon": -1.73205
                },
                "arrives": [ ]
            }
        ],
        "paths": [
            [
                {
                    "lat": 3.14159,
                    "lon": -2.71828
                },
                {
                    "lat": 3.14159,
                    "lon": -1.73205
                }
            ],
            [
                {
                    "lat": 3.14159,
                    "lon": -1.73205
                },
                {
                    "lat": 3.14159,
                    "lon": -1.61803
                },
                {
                    "lat": 3.14159,
                    "lon": -2.71828
                }
            ]
        ],
        "vehicles": [
            {
                "id": "vehicle1",
                "agency": "myAgency",
                "routeId": "transloc__720590",
                "lastSeen": 1334172614,
                "heading": 180,
                "coords": {
                    "lat": 3.14159,
                    "lon": -1.57079
                },
                "speed": 1.414,
                "iconURL": "http://feed.com/marker.png"
            }
        ]

    }

              
============
announcments
============

Parameters: none

Sample *response* ::

    [
        {
            "announcements": [
                {
                    "agency": "myAgency",
                    "title": "Subway is down, please take the bus.",
                    "date": "2012/04/11",
                    "timestamp": "1334118540",
                    "urgent": false,
                    "html": null
                },
                {
                    "agency": "myAgency",
                    "title": "Massive delays due to congestion",
                    "date": "2012/04/05",
                    "timestamp": "1333674900",
                    "urgent": false,
                    "html": null
                }
            ],
            "name": "myAgency"
        }

    ]

========
vehicles
========

Parameters

* *id* (required) - route ID
          
Sample *response* ::

    [
        {
            "id": "vehicle-0",
            "agency": "myAgency",
            "routeId": "abcdefg",
            "lastSeen": 1334173606,
            "heading": 212,
            "coords": {
                "lat": 3.14159,
                "lon": -2.71828
            },
            "speed": 0,
            "iconURL": "http://feed.com/marker.png"
        }
    ]

