@extends('layouts.app')

@section('title', 'MAM Tours | Discover Uganda and East Africa')
@section('body_class', 'travel-site')

@section('content')
<main class="travel-home">
    <div class="travel-promo">
        <span class="promo-mark"><i class="fas fa-sun"></i></span>
        <p><strong>Your next great story starts here.</strong> Thoughtfully planned journeys across Uganda and East Africa.</p>
        <a href="{{ url('/contact') }}">Talk to a travel expert <i class="fas fa-arrow-right"></i></a>
    </div>

    <section class="travel-hero">
        <div class="travel-hero-image" role="img" aria-label="Golden sunrise over the African savannah"></div>
        <div class="travel-hero-shade"></div>
        <div class="travel-hero-copy">
            <span class="eyebrow"><span></span> SMALL GROUPS · BIG EXPERIENCES</span>
            <h1>Go beyond<br>the <em>ordinary.</em></h1>
            <p>Meet mountain gorillas. Follow the Nile. Find your own wild corner of Africa with a trip made for the way you want to travel.</p>
            <a class="button button-light" href="#journeys">Explore our journeys <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="hero-caption">
            <span class="caption-line"></span>
            <span>Queen Elizabeth National Park, Uganda</span>
        </div>
        <div class="hero-pagination"><b>01</b><span></span>03</div>
    </section>

    <section class="trip-finder" aria-label="Find a trip">
        <div class="finder-intro">
            <span class="finder-icon"><i class="fas fa-compass"></i></span>
            <div><strong>Find your kind of adventure</strong><small>Where would you like to go?</small></div>
        </div>
        <label class="finder-field">
            <span>DESTINATION</span>
            <select id="tripDestination" aria-label="Choose destination">
                <option value="all">Anywhere in East Africa</option>
                <option value="uganda">Uganda</option>
                <option value="kenya">Kenya</option>
                <option value="tanzania">Tanzania</option>
            </select>
        </label>
        <label class="finder-field">
            <span>TRIP STYLE</span>
            <select id="tripStyle" aria-label="Choose trip style">
                <option value="all">Any experience</option>
                <option value="wildlife">Wildlife & safari</option>
                <option value="culture">Culture & community</option>
                <option value="active">Active escapes</option>
            </select>
        </label>
        <button class="button button-orange" id="findTrip" type="button">Find my trip <i class="fas fa-arrow-right"></i></button>
    </section>

    <section class="journeys section-wrap" id="journeys">
        <div class="section-heading">
            <div>
                <span class="eyebrow eyebrow-dark">MADE FOR THE MOMENTS THAT MATTER</span>
                <h2>Journeys worth<br><em>remembering.</em></h2>
            </div>
            <p>From misty forests to wide-open savannah, discover handpicked experiences with local insight and all the details taken care of.</p>
        </div>
        <div class="journey-tabs" role="group" aria-label="Filter journeys">
            <button class="journey-tab active" data-filter="all" type="button">All journeys <span>04</span></button>
            <button class="journey-tab" data-filter="uganda" type="button">Uganda</button>
            <button class="journey-tab" data-filter="kenya" type="button">Kenya</button>
            <button class="journey-tab" data-filter="tanzania" type="button">Tanzania</button>
        </div>

        <div class="journey-grid" id="journeyGrid">
            <article class="journey-card" data-destination="uganda" data-style="wildlife culture">
                <a class="journey-photo photo-gorillas" href="{{ url('/contact') }}" aria-label="Ask about the Gorilla Trails journey">
                    <span class="photo-tag"><i class="fas fa-leaf"></i> SIGNATURE JOURNEY</span>
                    <span class="photo-arrow"><i class="fas fa-arrow-up"></i></span>
                </a>
                <div class="journey-info">
                    <div class="journey-meta"><span>UGANDA</span><span>8 DAYS · 7 NIGHTS</span></div>
                    <h3>Gorilla Trails & Forest Stories</h3>
                    <p>Track mountain gorillas, walk the forest edge and connect with the people who call it home.</p>
                    <div class="journey-bottom"><span><i class="fas fa-map-marker-alt"></i> Bwindi · Lake Bunyonyi</span><a href="{{ url('/contact') }}">Discover trip <i class="fas fa-arrow-right"></i></a></div>
                </div>
            </article>
            <article class="journey-card" data-destination="uganda" data-style="wildlife active">
                <a class="journey-photo photo-nile" href="{{ url('/contact') }}" aria-label="Ask about the Nile and Savannah journey">
                    <span class="photo-tag"><i class="fas fa-star"></i> GUEST FAVOURITE</span>
                    <span class="photo-arrow"><i class="fas fa-arrow-up"></i></span>
                </a>
                <div class="journey-info">
                    <div class="journey-meta"><span>UGANDA</span><span>6 DAYS · 5 NIGHTS</span></div>
                    <h3>The Nile & the Savannah</h3>
                    <p>Watch the river thunder at Murchison Falls, then head out on safari across the plains.</p>
                    <div class="journey-bottom"><span><i class="fas fa-map-marker-alt"></i> Murchison Falls</span><a href="{{ url('/contact') }}">Discover trip <i class="fas fa-arrow-right"></i></a></div>
                </div>
            </article>
            <article class="journey-card" data-destination="kenya" data-style="wildlife culture">
                <a class="journey-photo photo-kenya" href="{{ url('/contact') }}" aria-label="Ask about the Savannah and Maasai Mara journey">
                    <span class="photo-tag"><i class="fas fa-binoculars"></i> WILDLIFE ESCAPE</span>
                    <span class="photo-arrow"><i class="fas fa-arrow-up"></i></span>
                </a>
                <div class="journey-info">
                    <div class="journey-meta"><span>KENYA</span><span>7 DAYS · 6 NIGHTS</span></div>
                    <h3>Savannah Skies & Maasai Mara</h3>
                    <p>Take in the great open plains, remarkable wildlife and warm local hospitality.</p>
                    <div class="journey-bottom"><span><i class="fas fa-map-marker-alt"></i> Nairobi · Maasai Mara</span><a href="{{ url('/contact') }}">Discover trip <i class="fas fa-arrow-right"></i></a></div>
                </div>
            </article>
            <article class="journey-card" data-destination="tanzania" data-style="wildlife active">
                <a class="journey-photo photo-tanzania" href="{{ url('/contact') }}" aria-label="Ask about the Kilimanjaro and Ngorongoro journey">
                    <span class="photo-tag"><i class="fas fa-mountain"></i> WILD & UNTAMED</span>
                    <span class="photo-arrow"><i class="fas fa-arrow-up"></i></span>
                </a>
                <div class="journey-info">
                    <div class="journey-meta"><span>TANZANIA</span><span>9 DAYS · 8 NIGHTS</span></div>
                    <h3>Kilimanjaro to Ngorongoro</h3>
                    <p>Pair dramatic highland views with a safari through one of Africa’s most iconic landscapes.</p>
                    <div class="journey-bottom"><span><i class="fas fa-map-marker-alt"></i> Arusha · Ngorongoro</span><a href="{{ url('/contact') }}">Discover trip <i class="fas fa-arrow-right"></i></a></div>
                </div>
            </article>
        </div>
        <p class="no-trips" id="noTrips" hidden>No journeys match those choices yet. Try another destination or trip style.</p>
        <div class="journey-footer"><a class="button button-outline" href="{{ url('/contact') }}">Plan a tailor-made trip <i class="fas fa-arrow-right"></i></a></div>
    </section>

    <section class="travel-promise">
        <div class="promise-photo" role="img" aria-label="A traveller looking out across a dramatic landscape"></div>
        <div class="promise-copy">
            <span class="eyebrow">THE MAM TOURS DIFFERENCE</span>
            <h2>Local knowledge.<br><em>Unforgettable days.</em></h2>
            <p>We bring the places we love closer to you. Our team helps shape every detail, from your first idea to the journey home.</p>
            <div class="promise-points">
                <div><span class="promise-check"><i class="fas fa-check"></i></span><span><strong>People who know the place</strong><small>Local insight makes the difference.</small></span></div>
                <div><span class="promise-check"><i class="fas fa-check"></i></span><span><strong>Thoughtful from start to finish</strong><small>Clear guidance at every step.</small></span></div>
                <div><span class="promise-check"><i class="fas fa-check"></i></span><span><strong>Made around you</strong><small>Choose a journey or let’s create one.</small></span></div>
            </div>
            <a class="promise-link" href="{{ url('/about') }}">Get to know us <i class="fas fa-arrow-right"></i></a>
        </div>
    </section>

    <section class="travel-cta">
        <span class="eyebrow">YOUR NEXT CHAPTER IS OUT THERE</span>
        <h2>Let’s make it <em>happen.</em></h2>
        <p>Tell us what you’re dreaming of. We’ll help you turn it into a journey.</p>
        <a class="button button-light" href="{{ url('/contact') }}">Start planning <i class="fas fa-arrow-right"></i></a>
        <span class="cta-mark cta-mark-one"></span><span class="cta-mark cta-mark-two"></span>
    </section>
    <footer class="travel-footer">
        <a class="footer-brand" href="{{ url('/') }}"><span class="footer-logo">M</span><span><strong>MAM TOURS</strong><small>TRAVEL & EXPERIENCES</small></span></a>
        <p>Made for curious travellers, with Uganda at heart.</p>
        <a href="{{ url('/contact') }}">Let’s talk <i class="fas fa-arrow-right"></i></a>
    </footer>
</main>
@endsection

@section('scripts')
<script>
(() => {
    const destination = document.getElementById('tripDestination');
    const style = document.getElementById('tripStyle');
    const cards = [...document.querySelectorAll('.journey-card')];
    const tabs = [...document.querySelectorAll('.journey-tab')];
    const empty = document.getElementById('noTrips');
    let selectedDestination = 'all';

    const filterTrips = () => {
        let visible = 0;
        cards.forEach(card => {
            const destinationMatch = selectedDestination === 'all' || card.dataset.destination === selectedDestination;
            const styleMatch = style.value === 'all' || card.dataset.style.split(' ').includes(style.value);
            card.hidden = !(destinationMatch && styleMatch);
            if (!card.hidden) visible++;
        });
        empty.hidden = visible > 0;
    };

    tabs.forEach(tab => tab.addEventListener('click', () => {
        selectedDestination = tab.dataset.filter;
        destination.value = selectedDestination;
        tabs.forEach(item => item.classList.toggle('active', item === tab));
        filterTrips();
    }));

    document.getElementById('findTrip').addEventListener('click', () => {
        selectedDestination = destination.value;
        tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.filter === selectedDestination));
        filterTrips();
        document.getElementById('journeys').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    style.addEventListener('change', filterTrips);
    destination.addEventListener('change', () => {
        selectedDestination = destination.value;
        tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.filter === selectedDestination));
        filterTrips();
    });
})();
</script>
@endsection
