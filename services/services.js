const vehicleType = document.getElementById("vehicleType");
const vehicleMake = document.getElementById("vehicleMake");
const vehicleModel = document.getElementById("vehicleModel");
const vehicleYear = document.getElementById("vehicleYear");
const findBtn = document.getElementById("findBtn");
const serviceResults = document.getElementById("serviceResults");
const checkoutSection = document.getElementById("checkoutSection");

// --- DATA: MAKES AND MODELS ---
// --- DATA: MAKES AND MODELS ---
const vehicleDatabase = {
    car: {
        Toyota: ["Corolla", "Prius", "Aqua", "Vitz", "Camry", "Axio", "Premio", "Allion", "Yaris"],
        Nissan: ["Sunny", "Wingroad", "Leaf", "Sylphy", "March", "Bluebird"],
        Honda: ["Civic", "Fit", "Accord", "Grace", "Insight", "City"],
        Suzuki: ["Alto", "Swift", "Wagon R", "Baleno", "K12"],
        Mazda: ["3", "6", "Demio", "Axela", "CX-3"],
        BMW: ["3 Series", "5 Series", "7 Series", "M3", "M5"],
        "Mercedes-Benz": ["A-Class", "C-Class", "E-Class", "S-Class", "CLA"], // <-- Quotes needed here
        Audi: ["A3", "A4", "A6", "A8", "RS3"],
        Volkswagen: ["Polo", "Golf", "Passat", "Jetta", "Beetle"],
        Mitsubishi: ["Lancer", "Mirage", "Lancer EX", "Galant"],
        Jaguar: ["XE", "XF", "XJ", "F-Type"]
    },
    van: {
        Toyota: ["Hiace", "KDH", "Hiace Grand Cabin", "Estima", "Voxy", "Noah"],
        Nissan: ["Caravan", "Vanette", "Elgrand", "Serena"],
        Suzuki: ["Every", "APV"],
        Mitsubishi: ["Grandis", "Delica", "Outlander"],
        "Mercedes-Benz": ["Sprinter", "Vito", "V-Class"], // <-- Quotes needed here
        Volkswagen: ["Transporter", "Caddy", "Multivan"]
    },
    suv: {
        Toyota: ["Fortuner", "RAV4", "Land Cruiser", "Prado", "Rush", "Harrier"],
        Honda: ["CR-V", "Vezel", "HR-V", "BR-V", "Pilot"],
        Nissan: ["X-Trail", "Juke", "Patrol", "Terrano"],
        Mitsubishi: ["Outlander", "Pajero", "Montero", "ASX"],
        Kia: ["Sportage", "Sorento", "Seltos"],
        Hyundai: ["Tucson", "Santa Fe", "Creta", "Palisade"],
        "Land Rover": ["Defender", "Range Rover", "Discovery", "Evoque", "Velar"], // <-- Quotes needed here
        BMW: ["X1", "X3", "X5", "X6", "X7"],
        Audi: ["Q3", "Q5", "Q7", "Q8"],
        Jaguar: ["F-Pace", "E-Pace", "I-Pace"],
        Jeep: ["Wrangler", "Cherokee", "Grand Cherokee"]
    },
    motorcycle: {
        Honda: ["Dio", "Hornet", "CBR 250", "Unicorn", "Activa", "CB350"],
        Yamaha: ["FZ", "R15", "MT-15", "Fazer", "YZF-R1"],
        Bajaj: ["Pulsar", "Dominar", "Discover", "Avenger"],
        "Royal Enfield": ["Classic 350", "Himalayan", "Meteor 350", "Interceptor 650"], // <-- Quotes needed here
        Suzuki: ["Gixxer", "Access", "V-Strom", "GSX-R"],
        TVS: ["Apache", "Ntorq", "Jupiter"],
        KTM: ["Duke 200", "Duke 390", "Adventure 390"],
        Kawasaki: ["Ninja 400", "Z900", "Versys 650"]
    },
    truck: {
        Isuzu: ["N-Series", "FRR", "FTR", "Elf"],
        Tata: ["LPT", "Ace", "LPK", "Xenon"],
        Mitsubishi: ["Canter", "Fighter", "FK"],
        UD: ["Quon", "Kuzer", "MK"],
        "Ashok Leyland": ["Comet", "Husky", "Captain"], // <-- Quotes needed here
        "Mercedes-Benz": ["Atego", "Actros", "Arocs"] // <-- Quotes needed here
    },
    bus: {
        Toyota: ["Coaster", "Hiace Commuter"],
        Mitsubishi: ["Rosa", "Aero", "Fuso"],
        Hyundai: ["County", "Universe", "Solati"],
        "Ashok Leyland": ["Viking", "Leopard", "Sunbeam"], // <-- Quotes needed here
        Isuzu: ["NQR Bus", "FRR Bus"]
    }
};

// --- DATA: SERVICES ---
// Specific prices for common models
const services = {
    "Corolla": [
        { name: "Oil Change", description: "Engine oil replacement and basic inspection.", price: "Rs. 5,000", time: "1 Hour" },
        { name: "Brake Service", description: "Brake inspection and brake pad checking.", price: "Rs. 8,500", time: "2 Hours" },
        { name: "AC Service", description: "Air conditioning inspection and maintenance.", price: "Rs. 7,500", time: "2 Hours" },
        { name: "Full Vehicle Service", description: "Complete vehicle inspection and maintenance.", price: "Rs. 25,000", time: "3 - 4 Hours" }
    ],
    "Wingroad": [
        { name: "Engine Diagnostic", description: "Full engine OBD-II scan and tune-up.", price: "Rs. 6,500", time: "1.5 Hours" },
        { name: "Oil Change", description: "Engine oil and filter replacement.", price: "Rs. 5,500", time: "1 Hour" },
        { name: "Suspension Check", description: "Shock absorbers and suspension bush inspection.", price: "Rs. 9,500", time: "2.5 Hours" }
    ],
    "Prius": [
        { name: "Hybrid System Service", description: "Hybrid system inspection and maintenance.", price: "Rs. 18,000", time: "2 Hours" },
        { name: "Oil Change", description: "Engine oil replacement and inspection.", price: "Rs. 5,500", time: "1 Hour" },
        { name: "Brake Service", description: "Brake inspection and maintenance.", price: "Rs. 9,000", time: "2 Hours" }
    ],
    "Civic": [
        { name: "Oil Change", description: "Engine oil replacement.", price: "Rs. 5,500", time: "1 Hour" },
        { name: "Brake Service", description: "Brake inspection and maintenance.", price: "Rs. 9,000", time: "2 Hours" },
        { name: "Engine Service", description: "Engine inspection and maintenance.", price: "Rs. 15,000", time: "3 Hours" }
    ],
    "Defender": [
        { name: "Full 4x4 Service", description: "Complete off-road vehicle maintenance.", price: "Rs. 45,000", time: "5 Hours" },
        { name: "Suspension & Chassis Check", description: "Heavy-duty suspension inspection.", price: "Rs. 15,000", time: "3 Hours" },
        { name: "Tire Rotation & Alignment", description: "4x4 wheel alignment and tire rotation.", price: "Rs. 8,500", time: "1.5 Hours" }
    ],
    "Range Rover": [
        { name: "Full Service", description: "Complete luxury SUV maintenance.", price: "Rs. 50,000", time: "5 Hours" },
        { name: "Air Suspension Diagnostic", description: "Air suspension system calibration and check.", price: "Rs. 18,500", time: "3 Hours" },
        { name: "AC Service", description: "Climate control inspection and refrigerant top-up.", price: "Rs. 12,000", time: "2 Hours" }
    ]
};

// --- DEFAULT SERVICE PACKAGE ---
// If a model is not in the 'services' object above, show these standard services.
// This guarantees the customer always has options (like changing tires).
const defaultServices = [
    { name: "Tire Replacement (Set of 4)", description: "Removal of old tires and installation of new tires, including balancing.", price: "Rs. 35,000", time: "2 Hours" },
    { name: "Wheel Alignment & Balancing", description: "Computerized alignment and wheel balancing for smooth driving.", price: "Rs. 5,500", time: "1.5 Hours" },
    { name: "Oil & Filter Change", description: "Standard engine oil replacement and filter change.", price: "Rs. 7,500", time: "1 Hour" },
    { name: "Brake Inspection & Service", description: "Brake pad inspection, cleaning, and fluid top-up.", price: "Rs. 10,000", time: "2 Hours" },
    { name: "Full Diagnostic & Inspection", description: "Complete vehicle health check and OBD-II computer scan.", price: "Rs. 8,000", time: "2 Hours" },
    { name: "Battery & Electrical Check", description: "Battery health, alternator, and starter motor inspection.", price: "Rs. 4,500", time: "1 Hour" }
];


// --- LOGIC: POPULATE DROPDOWNS ---

// 1. Populate Vehicle Type (Already done in HTML)

// 2. When Type changes, populate Makes
vehicleType.addEventListener("change", function () {
    const type = vehicleType.value;
    vehicleMake.innerHTML = '<option value="">-- Select Make --</option>';
    vehicleModel.innerHTML = '<option value="">-- Select Model --</option>';
    vehicleYear.innerHTML = '<option value="">-- Select Year --</option>';
    
    serviceResults.innerHTML = "";
    checkoutSection.classList.add("hidden");

    if (type) {
        const makes = Object.keys(vehicleDatabase[type]);
        makes.forEach(make => {
            vehicleMake.innerHTML += `<option value="${make}">${make}</option>`;
        });
        vehicleMake.disabled = false;
        vehicleModel.disabled = true;
        vehicleYear.disabled = true;
        findBtn.disabled = true;
    } else {
        vehicleMake.disabled = true;
        vehicleModel.disabled = true;
        vehicleYear.disabled = true;
        findBtn.disabled = true;
    }
});

// 3. When Make changes, populate Models
vehicleMake.addEventListener("change", function () {
    const type = vehicleType.value;
    const make = vehicleMake.value;
    
    vehicleModel.innerHTML = '<option value="">-- Select Model --</option>';
    vehicleYear.innerHTML = '<option value="">-- Select Year --</option>';
    serviceResults.innerHTML = "";
    checkoutSection.classList.add("hidden");

    if (make) {
        const models = vehicleDatabase[type][make];
        models.forEach(model => {
            vehicleModel.innerHTML += `<option value="${model}">${model}</option>`;
        });
        vehicleModel.disabled = false;
        vehicleYear.disabled = true;
        findBtn.disabled = true;
    } else {
        vehicleModel.disabled = true;
        vehicleYear.disabled = true;
        findBtn.disabled = true;
    }
});

// 4. When Model changes, populate Years (1990 to Current Year)
vehicleModel.addEventListener("change", function () {
    const currentYear = new Date().getFullYear();
    vehicleYear.innerHTML = '<option value="">-- Select Year --</option>';
    serviceResults.innerHTML = "";
    checkoutSection.classList.add("hidden");

    if (vehicleModel.value) {
        for (let y = currentYear; y >= 1990; y--) {
            vehicleYear.innerHTML += `<option value="${y}">${y}</option>`;
        }
        vehicleYear.disabled = false;
        findBtn.disabled = true;
    } else {
        vehicleYear.disabled = true;
        findBtn.disabled = true;
    }
});

// 5. When Year is selected, enable Find button
vehicleYear.addEventListener("change", function() {
    if (vehicleYear.value) {
        findBtn.disabled = false;
    } else {
        findBtn.disabled = true;
    }
});

// --- LOGIC: FIND SERVICES ---
function filterServices() {
    const model = vehicleModel.value;
    const year = vehicleYear.value;
    const make = vehicleMake.value;

    serviceResults.innerHTML = "";
    checkoutSection.classList.add("hidden");

    if (!model || !year) {
        serviceResults.innerHTML = `<div class="message">Please select all vehicle details first.</div>`;
        return;
    }

    // Check if we have specific services for this model
    // If not, use the defaultServices array so the user always has options (like tire changes)
    const selectedServices = services[model] || defaultServices;
    const displayName = `${year} ${make} ${model}`;

    serviceResults.innerHTML = `<h2 class="result-title">Available Services for ${displayName}</h2>`;

    selectedServices.forEach(function(service, index) {
        serviceResults.innerHTML += `
            <div class="service-card" id="card-${index}" onclick="selectService(${index})">
                <div class="service-info">
                    <h3>${service.name}</h3>
                    <p>${service.description}</p>
                    <p><strong>Estimated Time:</strong> ${service.time}</p>
                </div>
                <div class="price-section">
                    <p class="price-label">Estimated Price</p>
                    <p class="price">${service.price}</p>
                    <button class="book-button" type="button" onclick="event.stopPropagation(); selectService(${index})">
                        Select Service
                    </button>
                </div>
            </div>
        `;
        
        document.getElementById(`card-${index}`).dataset.name = service.name;
        document.getElementById(`card-${index}`).dataset.price = service.price;
        document.getElementById(`card-${index}`).dataset.time = service.time;
    });
}

// --- LOGIC: SELECT SERVICE & UPDATE CHECKOUT ---
function selectService(index) {
    document.querySelectorAll('.service-card').forEach(card => {
        card.classList.remove('selected');
    });

    const selectedCard = document.getElementById(`card-${index}`);
    selectedCard.classList.add('selected');
    
    document.querySelectorAll('.book-button').forEach(btn => {
        btn.innerText = "Select Service";
    });
    selectedCard.querySelector('.book-button').innerText = "Selected ✓";

    // Update summary data to include Year, Make, and Model
    const year = vehicleYear.value;
    const make = vehicleMake.value;
    const model = vehicleModel.value;
    const fullVehicleName = `${year} ${make} ${model}`;

    document.getElementById('summaryVehicle').innerText = fullVehicleName;
    document.getElementById('summaryService').innerText = selectedCard.dataset.name;
    document.getElementById('summaryTime').innerText = selectedCard.dataset.time;
    document.getElementById('summaryPrice').innerText = selectedCard.dataset.price;

    checkoutSection.classList.remove('hidden');
    checkoutSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function proceedToPayment() {
    alert("Redirecting to secure payment gateway... (Demo)");
    // window.location.href = '/payment-gateway';
}

// --- LOGIC: PROCEED TO PAYMENT (UPDATED) ---
function proceedToPayment() {
    // Grab the data from the summary box
    const vehicle = document.getElementById('summaryVehicle').innerText;
    const service = document.getElementById('summaryService').innerText;
    const time = document.getElementById('summaryTime').innerText;
    const price = document.getElementById('summaryPrice').innerText;

    // Save the data to sessionStorage to pass it to the next page
    const orderDetails = {
        vehicle: vehicle,
        service: service,
        time: time,
        price: price
    };
    sessionStorage.setItem('orderDetails', JSON.stringify(orderDetails));

    // Redirect to the Invoice/Billing page
    window.location.href = "../invoice/invoice.html";
}

// --- LOGIC: PROCEED TO PAYMENT ---
function proceedToPayment() {
    const vehicle = document.getElementById('summaryVehicle').innerText;
    const service = document.getElementById('summaryService').innerText;
    const time = document.getElementById('summaryTime').innerText;
    const price = document.getElementById('summaryPrice').innerText;

    // Send booking data to PHP to save in MySQL
    fetch('../invoice/save_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            vehicle: vehicle,
            service: service,
            time: time,
            price: price
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Booking saved! Redirect to invoice page and pass the ID in the URL
            window.location.href = `../invoice/invoice.php?id=${data.booking_id}`;
        } else {
            alert('Error saving booking: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error while saving booking.');
    });
}