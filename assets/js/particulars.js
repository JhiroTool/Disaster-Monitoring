/*
  particulars.js

  Centralized particulars -> color -> detail mapping based on the provided spreadsheet image.
  This file exposes a global `PARTICULARS_DETAILS` object and simple helpers for lookup.

  Usage (non-module, loaded before report.js):
    <script src="assets/js/particulars.js"></script>
    <script src="assets/js/report.js"></script>

  Then in report.js you can reference:
    PARTICULARS_DETAILS.getDetails('home_state', 'green') // returns array of strings
    PARTICULARS_DETAILS.LABELS.home_state // friendly label
*/

(function (window) {
  'use strict';

  const LABELS = {
    home_state: 'Current state of home/building after the typhoon',
    accessibility: 'Accessibility to road',
    power: 'Power Supply Status',
    water: 'Clean Water Supply',
    food: 'Food and essential supplies availability',
    flooding: 'Level of flooding',
    safety: 'Level of safety',
    readiness: 'Readiness to go back to school',
    transport: 'Transportation Status',
    eq_structural: 'Structural integrity after the earthquake',
    eq_road_access: 'Road access after the earthquake',
    eq_utilities: 'Utility services after the earthquake',
    eq_casualties: 'Casualty and injury status',
    eq_evacuation: 'Evacuation and shelter needs',
    eq_aftershocks: 'Aftershock activity and risk',
    volc_ashfall: 'Ashfall condition in community',
    volc_lava_flow: 'Lava or pyroclastic flow threat',
    volc_air_quality: 'Air quality and respiratory safety',
    volc_water: 'Water supply and contamination status',
    volc_evacuation: 'Evacuation progress and shelter status',
    volc_infrastructure: 'Critical infrastructure condition'
  };

  const DETAILS = {
    home_state: {
      green: [
        'Intact, no damage'
      ],
      orange: [
        'Minor damage (e.g., broken windows, minor leaks)'
      ],
      red: [
        'Moderate to severe damage (e.g., major structural damage, uninhabitable)'
      ]
    },
    accessibility: {
      green: [
        'Yes, fully accessible'
      ],
      orange: [
        'Partially accessible'
      ],
      red: [
        'No, blocked by debris/flooding'
      ]
    },
    power: {
      green: [
        'Fully operational (power available)'
      ],
      orange: [
        'Partial outages / intermittent outages'
      ],
      red: [
        'Complete power outage / power infrastructure damaged'
      ]
    },
    water: {
      green: [
        'Potable water available / normal supply'
      ],
      orange: [
        'Limited or contaminated supply / intermittent supply'
      ],
      red: [
        'No safe water / water system failure'
      ]
    },
    food: {
      green: [
        'Readily available / markets open'
      ],
      orange: [
        'Limited supplies / supply disruptions'
      ],
      red: [
        'Not available / markets closed'
      ]
    },
    flooding: {
      green: [
        'No flooding'
      ],
      orange: [
        'Minor flooding (below knee level)'
      ],
      red: [
        'Moderate to severe flooding (knee level or higher)'
      ]
    },
    safety: {
      green: [
        'Very safe / no injuries'
      ],
      orange: [
        'Somewhat safe, but with some concerns / minor injuries reported'
      ],
      red: [
        'Not safe at all / multiple injuries / life-threatening conditions'
      ]
    },
    readiness: {
      green: [
        'Yes, I feel safe / ready to return (schools operational)'
      ],
      orange: [
        'Somewhat ready / partial closures or reduced services'
      ],
      red: [
        "No, I don't feel safe at all / services suspended"
      ]
    },
    transport: {
      green: [
        'Yes, fully restored / roads clear'
      ],
      orange: [
        'Partially restored (limited routes or schedule)'
      ],
      red: [
        'No, transportation is not available / services suspended'
      ]
    },
    eq_structural: {
      green: [
        'Structures stable with only cosmetic cracks'
      ],
      orange: [
        'Visible structural cracks, inspection needed'
      ],
      red: [
        'Severe structural damage or collapse'
      ]
    },
    eq_road_access: {
      green: [
        'Roads clear and passable'
      ],
      orange: [
        'Partial debris blocking some access'
      ],
      red: [
        'Roads blocked or unsafe to use'
      ]
    },
    eq_utilities: {
      green: [
        'Power, water, and communications working normally'
      ],
      orange: [
        'Intermittent power or water outages'
      ],
      red: [
        'Utilities unavailable or infrastructure destroyed'
      ]
    },
    eq_casualties: {
      green: [
        'No injuries reported'
      ],
      orange: [
        'Minor injuries needing assistance'
      ],
      red: [
        'Serious injuries or trapped persons'
      ]
    },
    eq_evacuation: {
      green: [
        'No evacuation required'
      ],
      orange: [
        'Partial evacuation or temporary shelter needed'
      ],
      red: [
        'Immediate evacuation required'
      ]
    },
    eq_aftershocks: {
      green: [
        'Minimal aftershock activity'
      ],
      orange: [
        'Occasional aftershocks causing concern'
      ],
      red: [
        'Frequent strong aftershocks causing danger'
      ]
    },
    volc_ashfall: {
      green: [
        'No ashfall observed'
      ],
      orange: [
        'Light ashfall affecting visibility'
      ],
      red: [
        'Heavy ashfall causing hazards'
      ]
    },
    volc_lava_flow: {
      green: [
        'No lava or pyroclastic flow threat'
      ],
      orange: [
        'Nearby lava or pyroclastic activity being monitored'
      ],
      red: [
        'Active lava or pyroclastic flow threatening the area'
      ]
    },
    volc_air_quality: {
      green: [
        'Air quality safe with minimal irritation'
      ],
      orange: [
        'Masks recommended due to irritation'
      ],
      red: [
        'Hazardous air quality, breathing difficult'
      ]
    },
    volc_water: {
      green: [
        'Water supply clean and safe'
      ],
      orange: [
        'Possible ash contamination, limited safe water'
      ],
      red: [
        'Water unusable due to contamination'
      ]
    },
    volc_evacuation: {
      green: [
        'Residents safe at home'
      ],
      orange: [
        'Some residents evacuated to shelters'
      ],
      red: [
        'Full evacuation required'
      ]
    },
    volc_infrastructure: {
      green: [
        'Critical infrastructure operating'
      ],
      orange: [
        'Partial disruption of services'
      ],
      red: [
        'Critical infrastructure damaged or offline'
      ]
    }
  };

  const TYPHOON_KEYS = ['home_state', 'accessibility', 'power', 'water', 'food', 'flooding', 'safety', 'readiness', 'transport'];
  const EARTHQUAKE_KEYS = ['eq_structural', 'eq_road_access', 'eq_utilities', 'eq_casualties', 'eq_evacuation', 'eq_aftershocks'];
  const VOLCANIC_KEYS = ['volc_ashfall', 'volc_lava_flow', 'volc_air_quality', 'volc_water', 'volc_evacuation', 'volc_infrastructure'];
  const DISASTER_GROUPS = {
    default: TYPHOON_KEYS,
    typhoon: TYPHOON_KEYS,
    flood: TYPHOON_KEYS,
    'storm-surge': TYPHOON_KEYS,
    'super-typhoon': TYPHOON_KEYS,
    earthquake: EARTHQUAKE_KEYS,
    tsunami: EARTHQUAKE_KEYS,
    'volcanic-eruption': VOLCANIC_KEYS,
    'volcanic-activity': VOLCANIC_KEYS,
    ashfall: VOLCANIC_KEYS
  };

  function getDetails(particularKey, colorKey) {
    if (!particularKey) return [];
    const p = DETAILS[particularKey];
    if (!p) return [];
    if (colorKey) {
      return p[colorKey] ? p[colorKey].slice() : [];
    }
    // return flattened list (green then orange then red) if no color specified
    return [].concat(p.green || [], p.orange || [], p.red || []);
  }

  // Expose as global
  window.PARTICULARS_DETAILS = {
    LABELS: LABELS,
    DETAILS: DETAILS,
    getDetails: getDetails,
    GROUPS: DISASTER_GROUPS
  };

})(window);
