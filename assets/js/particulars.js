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
    transport: 'Transportation Status'
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
    }
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
    getDetails: getDetails
  };

})(window);
