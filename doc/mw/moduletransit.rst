###################
Transit Module
###################

The transit module provides a mobile interface for viewing live shuttle tracking and shuttle schedules.  Both running and offline shuttles can be displayed, as well as information about the transit authorities (phone numbers, desktop websites, etc) and any news or announcements from those authorities.

==============
Backend Design
==============

The Kurogo Transit backend is slightly different from the other DataModel-based classes.  The main transit class is TransitViewDataModel.  This class loads the entire feeds.ini file and provides the caller a unified view of all configured feeds.  This allows the transit backend to provide the following functionality:

------------------------
Inter-feed Id Namespaces
------------------------

Using TransitViewDataModel, a site with multiple transit feeds that share stop ids can provide links between the feeds.  For example if you have two GTFS databases which share stop ids, the "routes serving this stop" section of each stop page can reference routes in both feeds.  This is specified in feeds.ini with the ``system`` parameter.  Two feeds with the same system id are assumed to share route and stop id namespaces (e.g. stop id 10 refers to the same physical location in both feeds).  Correspondingly two feeds with different system ids are assumed to have separate but possibly overlapping route and stop id namespaces (e.g. stop id 10 in feed A is not the same stop as stop id 10 in feed B).

---------------------------------------
Support for Merged Live and Static Data
---------------------------------------

Using TransitViewDataModel, a site which has both static route data (e.g. GTFS) and live bus tracking data (e.g. NextBus) can display a merged view of this data.  When both live and static data are available, TransitViewDataModel will display live data overlaid on top of static data, preferring the static data only when live data for a particular field is unavailable.  This allows sites to display route information whether or not the live tracking system is currently available.

=============
Configuration
=============

Transit feed configuration is specified in feeds.ini.  Similar to other feeds, each transit feed is specified in a section. When using merged live and static data, both the live and static classes are specified in a single block.  For example, here a configuration to view three routes the Boston area's MBTA transit authority:

.. code-block:: ini

    [mbta]
    system = "nextbus"
     
    live_class = "NextBusDataModel"
    live_argument_keys[] = "VIEW_ROUTES_AS_LOOP"
    live_argument_vals[] = "1,701,747"
     
    static_class = "GTFSDataModel"
    static_argument_keys[] = "DB_FILE"
    static_argument_vals[] = DATA_DIR"/gtfs/gtfs-mbta.sqlite"
     
    all_argument_keys[] = "AGENCIES"
    all_argument_vals[] = "mbta"
    all_argument_keys[] = "ROUTE_WHITELIST"
    all_argument_vals[] = "1,701,747"


Note that there is a special section "defaults" which is used for default arguments for all transit classes.  Do not name your feed sections "defaults".


--------------
Feed Arguments
--------------

Due to the complexity of the transit configuration, feed arguments are specified in key and value arrays.  For feeds which have both live and static data sources, all_argument_keys/all_argument_values feed arguments apply to both live and static feeds.  For feed arguments which only apply to the live or static data,  live_argument_keys/live_argument_values and static_argument_keys/static_argument_values can be used instead.

In the feed configuration example above, ``VIEW_ROUTES_AS_LOOP = "1,701,747"`` will be passed to NextBusDataModel and ``DATABASE_FILE = "gtfs-mbta.sqlite"``  will be passed to GTFSDataModel.  Both classes will also receive the arguments ``AGENCIES = "mbta"`` and ``ROUTE_WHITELIST = "1,701,747"``.

**Common Arguments:**

* *AGENCIES* - (required) A string containing a comma-separated list of agency ids in the feed.
* *ROUTE_WHITELIST* - (optional) A string containing a comma-separated list of route ids.  If ROUTE_WHITELIST and ROUTE_BLACKLIST are not specified, all routes in each of the specified agencies will be shown.
* *ROUTE_BLACKLIST* - (optional) A string containing a comma-separated list of route ids to omit from the full list of route ids.  If ROUTE_WHITELIST and ROUTE_BLACKLIST are not specified, all routes in each of the specified agencies will be shown.
* *VIEW_ROUTES_AS_LOOP* - (optional) A string containing either a single asterix ``*`` (all routes) or a comma-separated list of route ids.  Sometimes transit feeds arbitrarily split routes which are loops into two directions.  This can cause the route to display in a way which does not make sense to the user.  This argument tells the backend to collapse these directions into a single loop.

**GTFS-Specific Arguments:**

* *DB_FILE* - (required) The location of the database containing the GTFS data.  For information about how to create this database, see below.
* *DB_TYPE* - (optional) If not specified, assumes a type of SQLite.
* *SPLIT_BY_HEADSIGN* - (optional) A string containing either a single asterix ``*`` (all routes) or a comma-separated list of route ids.  Instead of splitting routes by direction, splits them by the trip_headsign field in trips.txt.  This option will only work properly if trip_headsign is specified for all trips in trips.txt.
* *SCHEDULE_VIEW* - (optional) A string containing either a single asterix ``*`` (all routes) or a comma-separated list of route ids.  Attempts to display the GTFS data in schedule tables showing the next few vehicles.  Because GTFS data is meant for trip planning, it does not always contain enough data to order the stops in a route.  If your routes are display stops in the wrong order, see the section on stop orders below.

----------------------
Feed Argument Defaults
----------------------

You may wish to specify feed arguments which affect all feeds.  These are specified in a special "defaults" section.

.. code-block:: ini

    [defaults]
    CACHE_FOLDER = "Transit"
    CACHE_CLASS = "DataCache"


Note: the defaults section is the only way to pass arguments to the TransitViewDataModel class which presents the aggregated view of all routes.

--------------------------------
Overriding Stop and Route Fields
--------------------------------

In many cases you may wish to provide route and stop information which is not available in the feed.  In addition, the feed may contain information which is incorrect, poorly formatted or simply too long to display on mobile devices.  In this case you may provide overrides for various route and stop fields.  

Overrides are in the following format:

    *<all|live|static>_override_<route|stop>_<field key>_keys[] = "<route or stop id>"*
    
    *<all|live|static>_override_<route|stop>_<field key>_vals[] = "<replacement field value>"*

Overrides starting with ``all_override_`` apply to both live and static feeds.  Overrides starting with ``live_override_`` and ``static_override_`` apply to live and static feeds, respectively.  Overrides containing ``_override_route_`` and ``_override_stop_`` apply to routes and stops respectively and expect either a route or stop id in the ``_keys`` array.  

For example:

.. code-block:: ini

    live_override_route_description_keys[] = "520005"
    live_override_route_description_vals[] = "Runs 5:40am - 8:40am, Mon-Fri"  ; Quad Stadium
    
    live_override_route_summary_keys[] = "520005"
    live_override_route_summary_vals[] = "River Houses via Harvard Sq" ; Quad Stadium
    
    static_override_route_agency_keys[] = "saferidebostone"
    static_override_route_agency_vals[] = "mit"
    
    all_override_route_name_keys[] = "1"
    all_override_route_name_vals[] = "MBTA 1"
    
    all_override_stop_name_keys[] = "4905"
    all_override_stop_name_vals[] = "RIT Lot K Northbound"

The following are the fields which can be safely overridden.  While additional fields in route and stop information may be overridden, these fields are often dynamically generated and thus would not make sense to override to a single value.

**Route Fields:**

* *agency* - The agency id of the route.  Usually overridden to collapse routes into a single agency.
* *name* - The name of the route.
* *description* - A short description of the route.
* *summary* - A summary of where the route goes.  Displayed after the description if available.

**Stop Fields:**

* *name* - The name of the stop.  Usually overridden when the stop names are misleading or too long to fit on a mobile screen.
* *description* - A description of the stop.  Currently ignored by the transit modules.
 
--------------------------------------
Direction and Stop Order Configuration
--------------------------------------

Feeds that are meant for trip planning (e.g. GTFS) do not specify the full stop order of a route.  If the route vehicles do not stop at all stops, the transit backend may not be able to determine the full stop order and may display stops out of order, confusing users.  In addition, some feeds do not specify the names of their route directions.  In order to compensate for these feed issues, stop order configuration can be specified in ``config/transit-stoporder.ini``.

Route directions and stop orders  are specified by a section for and contain the following keys:

* *agency_id* - (required) The id of the agency the route belongs to.
* *route_id* - (required) The id of the route
* *direction_id* - (required) The direction id of this route direction (usually ``0``, ``1`` or ``loop`` but can be headsign name if ``SPLIT_BY_HEADSIGN`` was specified for this route)
* *direction_name* - (required) The name of the direction.  Can be an empty string if the route is a loop.
* *stop_ids* - (optional) An array of stop ids specifying the full stop order of the route.

For example the following section in transit-stoporder.ini specifies the real stop order of route id ``1`` of the agency ``GM``.  Because this route is a loop, it does not need a direction name and uses the ``loop`` direction:

.. code-block:: ini

    [WDIN]
    agency_id = "GM"
    route_id = "1"
    direction_id = "loop"
    direction_name = ""
    stop_ids[] = "GC"
    stop_ids[] = "TPD"
    stop_ids[] = "BN"
    stop_ids[] = "MM"
    stop_ids[] = "INN"
    stop_ids[] = "RH"
 
The following section in ``config/transit-stoporder.ini`` specifies the names of the two directions of route id ``33`` in agency ``RGRTA``.  This route has a well defined stop order so the stop ids array is not specified.

.. code-block:: ini

    [33_0]
    agency_id = "RGRTA"
    route_id = "33"
    direction_id = "0"
    direction_name = "Gleason Circle to Marketplace"
 
    [33_1]
    agency_id = "RGRTA"
    route_id = "33"
    direction_id = "1"
    direction_name = "Marketplace to Gleason Circle"
 
==============================
GTFS Database Converter
==============================

GTFS data is normally provided in a zip of CSV files.  However for large transit systems parsing the CSV files directly causes performance problems – some of the files may be up to 500K lines.  To solve this problem the transit backend supports uploading the GTFS zip file into an SQLite database.

-------------
Configuration
-------------

The GTFS database converter needs some configuration.  This information is stored in feeds-gtfs.ini.  At a bare minimum the system needs to know where the zip file is stored.

**Simple Configuration:**

* *zipfile* - (required) The full path to the GTFS zip file to be loaded into the database.
* *routes* - (optional) A whitelist of route ids.  Only data associated with these routes will be loaded into the database.  This can help performance on very large datasets.

You do not need to specify the database destination.  The new database will be written into a gtfs directory inside the site DATA_DIR (``data/gtfs/``).  The database will be named using the section name in the config file.  In the example above the database will be written to ``DATA_DIR"/gtfs/gtfs-mit.sqlite"`` because the entry above lives in an ``[mit]`` section.

For example:

.. code-block:: ini

    [mbta]
    zipfile = DATA_DIR"/gtfs/gtfs-mbta.zip"
    
    routes[] = "1"
    routes[] = "701"
    routes[] = "747"

Unfortunately, most GTFS files need a little more tweaking to get them to display properly.  As a result, the converter configuration also supports a variety of overrides and filters.  

**Field Overrides:**

Many transportation authorities auto generate GTFS data.  As a result route ids may change with every GTFS data set.  In order to avoid having to update the rest of the transit configuration, you can remap fields from one value to another while you are uploading your GTFS into the database.  Any field value in GTFS can be overridden, but the most commonly overridden are agency and route ids.

Field overrides take the following format:

    *<field name>_override_keys[] = "<old value in zip file>"*
    
    *<field name>_override_vals[] = "<new value for database>"*

Each override is an array so you can override more than one field value.

Note: Field overrides are run before route whitelist filtering so if you remap your route ids the route whitelist configuration should use the remapped ids.

For example the RGRTA transportation system uses different route ids for every GTFS data export.  The following config remaps the 8/29/2011-4/1/2012 data set route ids to route ids which can be used in the rest of the configuration: 

.. code-block:: ini

    [rgrta]
     
    zipfile = DATA_DIR"/gtfs/gtfs-rgrta-20110829-20120401.zip"
     
    ; Optional route id remap
    ; RGRTA changes these with every new data set so just remap
    ; so other config files don't have to know about the change
    route_id_override_keys[] = "8280" ; 28 RIT Campus Clockwise
    route_id_override_vals[] = "28"
     
    route_id_override_keys[] = "8282" ; 33 RIT Weekend/Holiday
    route_id_override_vals[] = "33"
    
    route_id_override_keys[] = "8279" ; 24 Marketplace Mall
    route_id_override_vals[] = "24"
    
    route_id_override_keys[] = "8455" ; 29 Tiger East End Express
    route_id_override_vals[] = "29"
    
    ; Optional route whitelist
    routes[] = "28" ; 28 RIT Campus Clockwise
    routes[] = "33" ; 33 RIT Weekend/Holiday
    routes[] = "24" ; 24 Marketplace Mall
    routes[] = "29" ; 29 Tiger East End Express

You can also use field overrides to make sure that your live and static parsers use the same ids.  For example the MBTA transit authority uses both GTFS and NextBus.  In GTFS the agency id is ``1`` and in NextBus it is ``mbta``.  Using a field override can convert the GTFS agency value to the NextBus agency value:

.. code-block:: ini

    [mbta]
     
    zipfile = DATA_DIR"/gtfs/gtfs-mbta.zip"
    
    ; Optional route whitelist
    routes[] = "1"
    routes[] = "701"
    routes[] = "747"
     
    ; Optional agency id remap
    agency_id_override_keys[] = "1"
    agency_id_override_vals[] = "mbta"

**Field Value Regular Expressions:**

Sometimes GTFS field values contain systematic errors which you do not want to list out.  Instead of overriding by field value, you can also run a regular expression over each field value before pushing it into the database.  Replacement strings can use variable replacement (e.g. \1, \2, etc).

Field value regular expressions take the following format:

    *<field name>_re_pattern = "/<regular expression to match on the field value>/"*
    
    *<field name>_re_replace = "<replacement string>"*

For example the RGRTA transit authority's GTFS which contains stop ids with their new and old versions concatenated together with the string ``_merged_``.  In order to avoid specifying each bad stop id individually we can apply a regular expression to remap them all to the new stop id:

.. code-block:: ini

    [rgrta]
     
    zipfile = DATA_DIR"/gtfs/gtfs-rgrta-20110829-20120401.zip"
    
    [...]
    
    ; RGRTA's merged data feeds have fake stop numbers which don't 
    ; correspond to the stop codes. This makes it hard for sysadmins 
    ; to maintain the lists of stops in transit-stoporder.ini
    stop_id_re_pattern = "/^(.+)_merged_.+$/"
    stop_id_re_replace = "\1"

In another example, sometimes GTFS creators use the ``route_short_name`` field to specify a non-user-friendly version of the name.  Since the transit module displays both short and long names, we can suppress the short name: 

.. code-block:: ini

    [mit]
     
    zipfile = DATA_DIR"/gtfs/gtfs-mit.zip"
     
    ; Hide bogus route short names
    route_short_name_re_pattern = "/^.*$/"
    route_short_name_re_replace = ""

**Uploading the Zip File**

Once the configuration file is set up, visit the ``gtfs2db`` page in the transit module (e.g. http://localhost/transit/gtfs2db).  This page is only accessible via localhost.  

This page will run a script which deletes any old database present and uploads the zip file specified in feeds-gtfs.ini into a new database.    Because GTFS data generally does not work correctly without some remapping configuration, we recommend that developers build the database and once it is working check it into the source repository in the site folder's data directory.  We do not recommend running this command on a production server.

====================
Maintenance Concerns
====================

When using route and stop overrides and specifying directions, you should be aware that each configuration line increases the maintenance cost of your transit module installation.  As routes and stops are added and removed the configuration will need to be updated.  

Some transit feeds like to renumber their route ids.  For example the Trapeze transit system uses different route ids for each GTFS export.  Because each GTFS export is only valid for 3-4 months, any configuration specifying route ids must be updated regularly.  For situations like this we recommend using the field remapping capabilities of the GTFS database converter.
