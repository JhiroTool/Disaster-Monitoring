document.addEventListener('DOMContentLoaded', function() {
    // Address data for CALABARZON
    const addressData = {
        'Batangas': {
            'Agoncillo': ['Adia', 'Bagong Sikat', 'Balangon', 'Banyaga', 'Barigon', 'Coral na Munti', 'Guitna', 'Mabayabas', 'Pamiga', 'Panhulan', 'Poblacion 1', 'Poblacion 2', 'Poblacion 3', 'Poblacion 4', 'Poblacion 5', 'Poblacion 6', 'Poblacion 7', 'Poblacion 8', 'Pook', 'San Jacinto', 'San Teodoro', 'Santa Cruz', 'Santo Tomas', 'Subic Ilaya', 'Subic Ibaba'],
            'Alitagtag': ['Balagbag', 'Dalisay', 'Dominador East', 'Dominador West', 'Munlawin', 'Ping-as', 'Poblacion', 'San Jose', 'Santa Cruz'],
            'Balayan': ['Baclaran', 'Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5', 'Barangay 6', 'Barangay 7', 'Barangay 8', 'Barangay 9', 'Barangay 10', 'Barangay 11', 'Barangay 12', 'Calan', 'Calzada', 'Canda', 'Carenahan', 'Cawit', 'Dalig', 'Dao', 'Duhatan', 'Durungao', 'Gimalas', 'Gitna', 'Lanatan', 'Lucsuhin', 'Magabe', 'Navotas', 'Patugo', 'Sambat', 'Sampaguita', 'San Juan', 'San Piro', 'Sukol', 'Tactac', 'Tanggoy', 'Tulo'],
            'Batangas City': ['Alangilan', 'Balagtas', 'Balete', 'Banaba Center', 'Banaba Kanluran', 'Banaba Silangan', 'Bolbok', 'Bucal', 'Calicanto', 'Catandala', 'Concepcion', 'Conde Itaas', 'Conde Labac', 'Cumba', 'Dumantay', 'Gulod Itaas', 'Gulod Labac', 'Libjo', 'Maapaz', 'Mahabang Dahilig', 'Mahabang Parang', 'Malibayo', 'Malitam', 'Maruclap', 'Pagkilatan', 'Paharang Kanluran', 'Paharang Silangan', 'Pallocan Kanluran', 'Pallocan Silangan', 'Pinamucan Ibaba', 'Pinamucan Proper', 'Sampaga', 'San Agapito', 'San Agustin', 'San Andres', 'San Antonio', 'San Isidro', 'San Jose', 'San Miguel', 'Santa Clara', 'Santa Rita', 'Santo Domingo', 'Santo Niño', 'Santo Tomas', 'Simlong', 'Sirang Lupa', 'Sorosoro Ibaba', 'Sorosoro Ilaya', 'Sorosoro Karsada', 'Tabangao Ambulong', 'Tabangao Aplaya', 'Talahib Pandayan', 'Talahib Payapa', 'Tingloy', 'Tulo', 'Wawa'],
            'Lipa City': ['Adya', 'Anilao-Labac', 'Antipolo del Norte', 'Antipolo del Sur', 'Bagong Pook', 'Balintawak', 'Banaybanay', 'Bolbok', 'Bugtong na Pulo', 'Bulacnin', 'Bulaklakan', 'Cabuyao', 'Dagatan', 'Duhatan', 'Gayabong', 'Guilam Kanluran', 'Guilam Silangan', 'Halang', 'Inosluban', 'Kayumanggi', 'Latag', 'Lodlod', 'Lumbang', 'Mabini', 'Malagonlong', 'Marawoy', 'Marauoy', 'Mataas na Lupa', 'Munting Pulo', 'Pagolingin Bata', 'Pagolingin East', 'Pagolingin West', 'Pangao', 'Pinagkawitan', 'Pinagtongulan', 'Plaridel', 'Poblacion Barangay 1', 'Poblacion Barangay 2', 'Poblacion Barangay 3', 'Poblacion Barangay 4', 'Poblacion Barangay 5', 'Poblacion Barangay 6', 'Poblacion Barangay 7', 'Poblacion Barangay 8', 'Poblacion Barangay 9', 'Poblacion Barangay 10', 'Poblacion Barangay 11', 'Poblacion Barangay 12', 'Pusil', 'Quezon', 'Rizal', 'Sabang', 'Sampaguita', 'San Benito', 'San Carlos', 'San Celestino', 'San Francisco', 'San Guillermo', 'San Jose', 'San Lucas', 'San Salvador', 'Santo Niño', 'Santo Toribio', 'Sapac', 'Sico', 'Talisay', 'Tambo', 'Tangob', 'Tibig', 'Tipacan'],
            'Tanauan City': ['Altura Bata', 'Altura Matanda', 'Altura-South', 'Ambulong', 'Bagbag', 'Bagumbayan', 'Balele', 'Banjo East', 'Banjo Laurel', 'Banjo West', 'Bilog-bilog', 'Boot', '브라gy 1 (Poblacion)', 'Brgy 2 (Poblacion)', 'Brgy 3 (Poblacion)', 'Brgy 4 (Poblacion)', 'Brgy 5 (Poblacion)', 'Brgy 6 (Poblacion)', 'Darasa', 'Gonzales', 'Hidalgo', 'Janopol', 'Janopol Oriental', 'Laurel', 'Malaking Pulo', 'Maria Paz', 'Montana', 'Natatas', 'Pagaspas', 'Pantay Bata', 'Pantay Matanda', 'Sala', 'Sambat', 'San Jose', 'Santol', 'Sulpoc', 'Suplang', 'Talaga', 'Tinurik', 'Ulango', 'Wawa']
        },
        'Cavite': {
            'Bacoor City': ['Aniban I', 'Aniban II', 'Aniban III', 'Aniban IV', 'Aniban V', 'Bayanan', 'Dasmariñas', 'Dulong Bayan', 'Habay I', 'Habay II', 'Kaingin', 'Ligas I', 'Ligas II', 'Ligas III', 'Maliksi I', 'Maliksi II', 'Maliksi III', 'Molino I', 'Molino II', 'Molino III', 'Molino IV', 'Molino V', 'Molino VI', 'Molino VII', 'Morcelo', 'Niog I', 'Niog II', 'Niog III', 'Panapaan I', 'Panapaan II', 'Panapaan III', 'Panapaan IV', 'Panapaan V', 'Panapaan VI', 'Panapaan VII', 'Panapaan VIII', 'Queens Row Central', 'Queens Row East', 'Queens Row West', 'Real I', 'Real II', 'Salinas I', 'Salinas II', 'Salinas III', 'San Nicolas I', 'San Nicolas II', 'San Nicolas III', 'Sineguelasan', 'Springville', 'Tabing Dagat', 'Talaba I', 'Talaba II', 'Talaba III', 'Talaba IV', 'Talaba V', 'Talaba VI', 'Talaba VII', 'Zapote I', 'Zapote II', 'Zapote III', 'Zapote IV', 'Zapote V'],
            'Dasmariñas City': ['Bagong Bayan', 'Burol I', 'Burol II', 'Burol III', 'Datu Esmael', 'Langkiwa', 'Paliparan I', 'Paliparan II', 'Paliparan III', 'Salitran I', 'Salitran II', 'Salitran III', 'Salitran IV', 'Sampaloc I', 'Sampaloc II', 'Sampaloc III', 'Sampaloc IV', 'San Agustin I', 'San Agustin II', 'San Agustin III', 'San Jose', 'San Miguel I', 'San Miguel II', 'Zone I (Poblacion)', 'Zone II (Poblacion)', 'Zone III (Poblacion)', 'Zone IV (Poblacion)'],
            'General Trias City': ['Alingaro', 'Arnaldo', 'Bacao I', 'Bacao II', 'Bagumbayan', 'Biclatan', 'Buenavista I', 'Buenavista II', 'Buenavista III', 'Corregidor', 'Dulong Bayan', 'Gov. Ferrer', 'Javalera', 'Manggahan', 'Navarro', 'Ninety Six', 'Pasong Kawayan I', 'Pasong Kawayan II', 'Pinagtipunan', 'Poblacion', 'Prinza', 'Sabang', 'San Francisco', 'San Gabriel', 'San Juan I', 'San Juan II', 'Santisima Trinidad', 'Santiago', 'Tapia', 'Tejero', 'Vibora'],
            'Imus City': ['Alapan I-A', 'Alapan I-B', 'Alapan I-C', 'Alapan II-A', 'Alapan II-B', 'Anabu I-A', 'Anabu I-B', 'Anabu I-C', 'Anabu I-D', 'Anabu I-E', 'Anabu I-F', 'Anabu I-G', 'Anabu I-H', 'Anabu I-I', 'Anabu I-J', 'Anabu II-A', 'Anabu II-B', 'Anabu II-C', 'Anabu II-D', 'Anabu II-E', 'Anabu II-F', 'Bayan Luma I', 'Bayan Luma II', 'Bayan Luma III', 'Bayan Luma IV', 'Bayan Luma V', 'Bayan Luma VI', 'Bayan Luma VII', 'Bayan Luma VIII', 'Bayan Luma IX', 'Bucandala I', 'Bucandala II', 'Bucandala III', 'Bucandala IV', 'Bucandala V', 'Buhay na Tubig', 'Calabash', 'Carsadang Bago I', 'Carsadang Bago II', 'Green Valley', 'Maharlika', 'Malagasang I-A', 'Malagasang I-B', 'Malagasang I-C', 'Malagasang I-D', 'Malagasang I-E', 'Malagasang I-F', 'Malagasang I-G', 'Malagasang II-A', 'Malagasang II-B', 'Malagasang II-C', 'Malagasang II-D', 'Malagasang II-E', 'Malagasang II-F', 'Malagasang II-G', 'Medicion I-A', 'Medicion I-B', 'Medicion I-C', 'Medicion I-D', 'Medicion II-A', 'Medicion II-B', 'Medicion II-C', 'Medicion II-D', 'Medicion II-E', 'Medicion II-F', 'Palico I', 'Palico II', 'Palico III', 'Palico IV', 'Pasong Buaya I', 'Pasong Buaya II', 'Pinagbuhatan', 'Poblacion I-A', 'Poblacion I-B', 'Poblacion I-C', 'Poblacion II-A', 'Poblacion II-B', 'Poblacion III-A', 'Poblacion III-B', 'Poblacion IV-A', 'Poblacion IV-B', 'Poblacion IV-C', 'Poblacion IV-D', 'Punta I', 'Punta II', 'Punta III', 'Tanzang Luma I', 'Tanzang Luma II', 'Tanzang Luma III', 'Tanzang Luma IV', 'Tanzang Luma V', 'Tanzang Luma VI', 'Toclong I-A', 'Toclong I-B', 'Toclong I-C', 'Toclong II-A', 'Toclong II-B'],
            'Tagaytay City': ['Asisan', 'Bagong Tubig', 'Calabuso', 'Dapdap East', 'Dapdap West', 'Francisco', 'Guinhawa North', 'Guinhawa South', 'Iruhin Central', 'Iruhin East', 'Iruhin West', 'Kaybagal Central', 'Kaybagal North', 'Kaybagal South', 'Mag-asawang Ilat', 'Maharlika East', 'Maharlika West', 'Mendez Crossing East', 'Mendez Crossing West', 'Maitim 2nd Central', 'Maitim 2nd East', 'Maitim 2nd West', 'Neogan', 'Patutong Malaki North', 'Patutong Malaki South', 'Sambong', 'San Jose', 'Silang Junction North', 'Silang Junction South', 'Sungay East', 'Sungay West', 'Tolentino East', 'Tolentino West', 'Zambal']
        },
        'Laguna': {
            'Calamba City': ['Bagong Kalsada', 'Banlic', 'Bañadero', 'Barandal', 'Batino', 'Bubuyan', 'Bucal', 'Bunggo', 'Burol', 'Camaligan', 'Canlubang', 'Halang', 'Hornalan', 'Kay-Anlog', 'La Mesa', 'Laguerta', 'Lecheria', 'Lingga', 'Looc', 'Mabato', 'Makiling', 'Mapagong', 'Masili', 'Maunong', 'Mayapa', 'Milagrosa', 'Paciano Rizal', 'Palingon', 'Palo-Alto', 'Pansol', 'Parian', 'Poblacion', 'Punta', 'Puting Lupa', 'Real', 'Saimsim', 'Sampiruhan', 'San Cristobal', 'San Jose', 'Sirang Lupa', 'Sucol', 'Turbina', 'Ulango', 'Uwisan'],
            'San Pablo City': ['I-A (Sambat)', 'I-B (City Proper)', 'I-C (Bagong Bayan)', 'II-A (Triangulo)', 'II-B (Guadalupe)', 'II-C (Unson)', 'II-D (Bulante)', 'II-E (San Anton)', 'II-F (Villa Rey)', 'III-A (Labak/De Roma)', 'III-B (Villongco)', 'III-C (Villajuan)', 'III-D (Villa Arcenas)', 'III-E (Tejeros)', 'III-F (Villa Hidalgo)', 'IV-A (Banaybanay)', 'IV-B (Malamig)', 'IV-C (Dolores)', 'V-A (Santisimo Rosario)', 'V-B (San Lucas 1)', 'V-C (San Lucas 2)', 'V-D (San Mateo)', 'VI-A (Mavendia)', 'VI-B (San Jose)', 'VI-C (Candelaria)', 'VI-D (San Francisco)', 'VI-E (Bagong Pook)', 'VII-A (San Buenaventura)', 'VII-B (San Crispin)', 'VII-C (San Cristobal)', 'VII-D (San Diego)', 'VII-E (San Gregorio)', 'VII-F (San Ignacio)', 'VII-G (San Joaquin)', 'VII-H (San Lorenzo)', 'VII-I (San Nicolas)', 'VII-J (San Pedro)', 'VII-K (San Roque)', 'VII-L (San Vicente)', 'Atisan', 'Bautista', 'Concepcion', 'Del Remedio', 'Dolores', 'Imok', 'Lumbang', 'Magdalena', 'Nagcarlan', 'San Antonio 1', 'San Antonio 2', 'San Bartolome', 'San Miguel', 'Santa Barbara', 'Santa Cruz', 'Santa Elena', 'Santa Filomena', 'Santa Isabel', 'Santa Maria', 'Santa Monica', 'Santa Veronica', 'Santo Angel', 'Santo Niño (Armal)', 'Soledad'],
            'Santa Rosa City': ['Aplaya', 'Balibago', 'Caingin', 'Colamba', 'Dila', 'Dita', 'Don Jose', 'Ibaba', 'Kanluran', 'Labas', 'Laguna', 'Malitlit', 'Malusak', 'Market Area', 'Matina', 'Pooc', 'Pulong Santa Cruz', 'Sinalhan', 'Santo Domingo', 'Tagapo'],
            'Biñan City': ['Biñan', 'Bungahan', 'Canlalay', 'Casile', 'De La Paz', 'Ganado', 'Langkiwa', 'Loma', 'Malaban', 'Malamig', 'Mamplasan', 'Platero', 'Poblacion', 'San Antonio', 'San Francisco', 'San Jose', 'San Vicente', 'Santo Domingo', 'Santo Niño', 'Soro-soro', 'Sstian', 'Tubigan', 'Zapote'],
            'Cabuyao City': ['Baclaran', 'Banay-banay', 'Banlic', 'Bigaa', 'Butong', 'Casile', 'Diezmo', 'Gulod', 'Mamatid', 'Marinig', 'Niugan', 'Pittland', 'Poblacion Dos', 'Poblacion Tres', 'Poblacion Uno', 'Pulo', 'Sala', 'San Isidro']
        },
        'Quezon': {
            'Lucena City': ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5', 'Barangay 6', 'Barangay 7', 'Barangay 8', 'Barangay 9', 'Barangay 10', 'Barangay 11', 'Barra', 'Bocohan', 'Cotta', 'Dalahican', 'Domoit', 'Gulang-Gulang', 'Ibabang Dupay', 'Ibabang Iyam', 'Ibabang Talim', 'Ilayang Dupay', 'Ilayang Iyam', 'Ilayang Talim', 'Isabang', 'Kalubihan', 'Kanlurang Mayao', 'Kulapi', 'Mayao Castillo', 'Mayao Crossing', 'Mayao Kanluran', 'Mayao Parada', 'Ransohan', 'Salinas', 'Silangang Mayao'],
            'Tayabas City': ['Alitao', 'Alsam Ibaba', 'Alsam Ilaya', 'Ayaas', 'Baguio', 'Banilad', 'Bukal', 'Bukal na Pag-asa', 'Dapdap', 'Domoit Kanluran', 'Domoit Silangan', 'Gibanga', 'Ibas', 'Ilasan Ibaba', 'Ilasan Ilaya', 'Ipilan', 'Isabang', 'Kamay', 'Kanlurang Calumpang', 'Katigan Kanluran', 'Katigan Silangan', 'Lakawan', 'Lawigue', 'Lita', 'Malabanban Norte', 'Malabanban Sur', 'Masin', 'Mate', 'Mateuna', 'Mayabobo', 'Nangka', 'Opias', 'Palale', 'Pandakaki', 'Papaya', 'Piis', 'Poblacion Dos', 'Poblacion Quatro', 'Poblacion Tres', 'Poblacion Uno', 'Potol', 'San Agustin', 'San Isidro Norte', 'San Isidro Sur', 'San Roque', 'Silangang Calumpang', 'Talolong', 'Tamlong', 'Tongko', 'Villa Floresta', 'Walay'],
            'Candelaria': ['Bagong Silang', 'Bago', 'Buenavista', 'Bukal', 'Kinatihan 1st', 'Kinatihan 2nd', 'Malabanban', 'Mangilag Norte', 'Mangilag Sur', 'Masalukot 1st', 'Masalukot 2nd', 'Masalukot 3rd', 'Masalukot 4th', 'Masalukot 5th', 'Mayabobo', 'Pahinga Norte', 'Pahinga Sur', 'Poblacion', 'San Andres', 'Tiaong', 'Malawig']
        },
        'Rizal': {
            'Antipolo City': ['Bagong Nayon', 'Beverly Hills', 'Calawis', 'Cupang', 'Dalig', 'dela Paz', 'Forestry', 'Inarawan', 'Mambugan', 'Mayamot', 'Poblacion', 'San Isidro', 'San Jose', 'San Juan', 'San Luis', 'San Roque', 'Santa Cruz', 'Santo Niño', 'Taktak'],
            'Cainta': ['Dayap', 'San Andres', 'San Isidro', 'San Juan', 'Santo Domingo', 'Santo Niño'],
            'Taytay': ['Bagumbayan', 'Bayog', 'San Isidro', 'San Juan', 'Santa Ana']
        }
    };

    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');
    const severitySelect = document.getElementById('severity');
    const severityColorSelect = document.getElementById('severity_color');

    // Get defaults from window.formDefaults if available
    const formDefaults = window.formDefaults || {};
    const assessmentsDefaults = formDefaults.assessments || {};
    
    // Handle province change
    provinceSelect.addEventListener('change', function() {
        const selectedProvince = this.value;
        citySelect.innerHTML = '<option value="">Choose City/Municipality</option>';
        barangaySelect.innerHTML = '<option value="">Choose Barangay</option>';
        
        if (selectedProvince && addressData[selectedProvince]) {
            const cities = Object.keys(addressData[selectedProvince]);
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        }
        
        // Restore selected city if available
        if (formDefaults.city && citySelect.querySelector(`option[value="${formDefaults.city}"]`)) {
            citySelect.value = formDefaults.city;
            citySelect.dispatchEvent(new Event('change'));
        }
    });

    // Handle city change
    citySelect.addEventListener('change', function() {
        const selectedProvince = provinceSelect.value;
        const selectedCity = this.value;
        barangaySelect.innerHTML = '<option value="">Choose Barangay</option>';
        
        if (selectedProvince && selectedCity && addressData[selectedProvince] && addressData[selectedProvince][selectedCity]) {
            const barangays = addressData[selectedProvince][selectedCity];
            barangays.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay;
                option.textContent = barangay;
                barangaySelect.appendChild(option);
            });
        }
        
        // Restore selected barangay if available
        if (formDefaults.barangay && barangaySelect.querySelector(`option[value="${formDefaults.barangay}"]`)) {
            barangaySelect.value = formDefaults.barangay;
        }
    });

    // Restore province after page load
    if (formDefaults.province) {
        provinceSelect.value = formDefaults.province;
        provinceSelect.dispatchEvent(new Event('change'));
    }

    // Restore assessments if present
    if (assessmentsDefaults && typeof assessmentsDefaults === 'object') {
        Object.keys(assessmentsDefaults).forEach(key => {
            const sel = document.querySelector(`select[name="assessments[${key}]"]`);
            if (sel) sel.value = assessmentsDefaults[key];
        });
    }

    // Severity -> color mapping (base on professor image guidance)
    const severityColorMap = {
        // Green group
        'green-1': 'green',
        'green-2': 'green',
        'green-3': 'green',
        'green-4': 'green',
        'green-5': 'green',
        // Orange group
        'orange-1': 'orange',
        'orange-2': 'orange',
        'orange-3': 'orange',
        'orange-4': 'orange',
        'orange-5': 'orange',
        // Red group
        'red-1': 'red',
        'red-2': 'red',
        'red-3': 'red',
        'red-4': 'red',
        'red-5': 'red'
    };

    function syncSeverityColorFromSeverity() {
        if (!severitySelect || !severityColorSelect) return;
        const sev = severitySelect.value;
        const color = severityColorMap[sev] || '';
        if (color) {
            severityColorSelect.value = color;
        }
    }

    // When severity changes, auto-select color
    if (severitySelect) {
        severitySelect.addEventListener('change', function() {
            syncSeverityColorFromSeverity();
        });
    }

    // Restore severity color from formDefaults if present
    if (formDefaults.severity_color && severityColorSelect) {
        severityColorSelect.value = formDefaults.severity_color;
    } else {
        // If no explicit color chosen, infer from severity
        syncSeverityColorFromSeverity();
    }

    // Particulars (9 items) -> color-specific detail lists
    const particularSelect = document.getElementById('particular');
    const particularColorSelect = document.getElementById('particular_color');
    const particularDetailSelect = document.getElementById('particular_detail');

    // Use centralized particulars mapping from assets/js/particulars.js (PARTICULARS_DETAILS)
    const particularDetails = window.PARTICULARS_DETAILS ? window.PARTICULARS_DETAILS.DETAILS : {};

    function populateParticularDetail() {
        console.log('=== STARTING populateParticularDetail ===');
        
        if (!particularDetailSelect) {
            console.error('ERROR: particularDetailSelect element not found!');
            return;
        }

        // Get current selections
        const selectedParticular = particularSelect ? particularSelect.value : '';
        const selectedColor = particularColorSelect ? particularColorSelect.value : '';
        
        console.log('Current selections - Particular:', selectedParticular, 'Color:', selectedColor);

        // NUCLEAR OPTION: Completely rebuild the select element
        particularDetailSelect.innerHTML = '';
        particularDetailSelect.multiple = false;
        particularDetailSelect.size = 1;
        particularDetailSelect.name = 'particular_detail';
        particularDetailSelect.removeAttribute('class');

        // Add default placeholder
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Choose detail...';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        particularDetailSelect.appendChild(defaultOption);

        // Exit early if no selections made
        if (!selectedParticular || !selectedColor) {
            console.log('No particular/color selected, showing placeholder only');
            return;
        }

        // Check data availability
        if (!window.PARTICULARS_DETAILS || !window.PARTICULARS_DETAILS.DETAILS) {
            console.error('ERROR: PARTICULARS_DETAILS not available');
            return;
        }

        const data = window.PARTICULARS_DETAILS.DETAILS;
        if (!data[selectedParticular]) {
            console.error('ERROR: No data for particular:', selectedParticular);
            return;
        }

        if (!data[selectedParticular][selectedColor]) {
            console.error('ERROR: No data for color:', selectedColor, 'in particular:', selectedParticular);
            return;
        }

        // Get the exact details for this color ONLY
        const detailsForSelectedColor = data[selectedParticular][selectedColor];
        console.log('Details found for', selectedColor, ':', detailsForSelectedColor);

        // Add ONLY the details for the selected color
        if (Array.isArray(detailsForSelectedColor) && detailsForSelectedColor.length > 0) {
            detailsForSelectedColor.forEach((detailText, idx) => {
                const opt = document.createElement('option');
                opt.value = detailText;
                opt.textContent = detailText;
                particularDetailSelect.appendChild(opt);
                console.log('Added option #' + (idx + 1) + ':', detailText);
            });
        }

        console.log('=== FINISHED populateParticularDetail ===');
    }

    if (particularSelect) {
        // Restore selected particular
        if (formDefaults.particular) {
            particularSelect.value = formDefaults.particular;
        }
        particularSelect.addEventListener('change', populateParticularDetail);
    }

    if (particularColorSelect) {
        // Restore selected color for particular
        if (formDefaults.particular_color) {
            particularColorSelect.value = formDefaults.particular_color;
        }
        particularColorSelect.addEventListener('change', populateParticularDetail);
    }

    // If both are set from defaults, populate detail list on load
    if ((formDefaults.particular || '') && (formDefaults.particular_color || '')) {
        populateParticularDetail();
    }

    // Image upload functionality
    const imageInput = document.getElementById('emergency_image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const fileUploadWrapper = document.querySelector('.file-upload-wrapper');
    
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, or GIF).');
                    this.value = '';
                    return;
                }
                
                // Validate file size (5MB max)
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if (file.size > maxSize) {
                    alert('File size must be less than 5MB.');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                    fileUploadWrapper.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

function removeImage() {
    const imageInput = document.getElementById('emergency_image');
    const imagePreview = document.getElementById('imagePreview');
    const fileUploadWrapper = document.querySelector('.file-upload-wrapper');
    
    imageInput.value = '';
    imagePreview.style.display = 'none';
    fileUploadWrapper.style.display = 'block';
}
