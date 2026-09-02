// ======================== HERO CAROUSEL PROFESIONAL ========================
(function() {
  const cardsData = [
    { amount: "$140.000", bg: "linear-gradient(135deg, #1E40AF, #3B82F6)", label: "#BAE6FD", val: "#FFFFFF" },
    { amount: "$120.000", bg: "linear-gradient(135deg, #FFFFFF, #EFF6FF)", label: "#1E40AF", val: "#0A1628" },
    { amount: "$200.000", bg: "linear-gradient(135deg, #3B82F6, #60A5FA)", label: "#FFFFFF", val: "#FFFFFF" },
    { amount: "$80.000",  bg: "linear-gradient(135deg, #DBEAFE, #F0F9FF)", label: "#1E40AF", val: "#0A1628" }
  ];

  const slides = [
    { type: "cards", title: "Tarjetas de Regalo", sub: "El regalo perfecto para cada ocasión" },
    { type: "promo", badge: "Nueva colección", title: "Color Dreamers", subtitle: "Viste la temporada con estilo, comodidad y color", btnText: "Explorar colección", link: "/compra" },
    { type: "flash", badge: "Oferta relámpago", title: "Hasta 40% OFF", subtitle: "En productos seleccionados por tiempo limitado", btnText: "Aprovechar oferta", link: "/compra", showTimer: true },
    { type: "promo", badge: "Bebés felices", title: "20% OFF + envío", subtitle: "Comodidad suave para los más pequeños", btnText: "Ver bebés", link: "/compra" },
    { type: "promo", badge: "Escolar listo", title: "Accesorios escolares", subtitle: "Mochilas, loncheras y detalles para el regreso", btnText: "Ver accesorios", link: "/compra" },
    { type: "shipping", badge: "Envío gratis", title: "Sin mínimo de compra", subtitle: "Válido durante las próximas 24 horas", btnText: "Comprar ahora", link: "/compra" }
  ];

  const baseUrl = typeof APP_URL !== "undefined" ? APP_URL : "";
  let currentIndex = 0;
  let autoInterval;
  let isPaused = false;
  let slideDuration = 6500;
  let progressBar = null;
  let wrapper = null;
  let carouselSection = null;
  const totalSlides = slides.length;

  function notify(title, message, type = "info") {
    if (typeof window.showToast === "function") {
      window.showToast({ title, message, type });
    }
  }

  function createGiftCard(card) {
    const el = document.createElement("div");
    el.className = "gift-card";
    el.style.background = card.bg;
    el.style.color = card.val;
    el.innerHTML = `
      <div class="gift-card-top">
        <span>ANGELOW</span>
        <span class="gift-icon">★</span>
      </div>
      <div>
        <div class="gift-label">BONO REGALO</div>
        <div class="gift-amount">${card.amount}</div>
      </div>`;
    el.addEventListener("click", () => notify("Bono regalo", "Selecciona un producto para continuar", "info"));
    return el;
  }

  function buildSlides() {
    const fragment = document.createDocumentFragment();
    slides.forEach((slide) => {
      const slideDiv = document.createElement("div");
      slideDiv.className = `slide-base ${slide.type === "cards" ? "slide-cards" : "slide-promo"}`;

      if (slide.type === "cards") {
        slideDiv.innerHTML = `
          <div class="slide-copy">
            <span class="promo-badge">${slide.badge || "AngeLow gift"}</span>
            <h2 class="carousel-title">${slide.title}</h2>
            <p class="promo-subtitle">${slide.sub}</p>
          </div>`;
        const grid = document.createElement("div");
        grid.className = "cards-grid";
        cardsData.forEach(c => grid.appendChild(createGiftCard(c)));
        slideDiv.appendChild(grid);
      } else {
        slideDiv.innerHTML = `
          <div class="promo-content">
            <span class="promo-badge">${slide.badge}</span>
            <h2 class="promo-title">${slide.title}</h2>
            <p class="promo-subtitle">${slide.subtitle}</p>
            ${slide.showTimer ? '<div class="flash-timer" id="countdownTimer" aria-label="Temporizador de oferta"></div>' : ""}
            <button class="btn-gold slide-action" type="button" data-link="${slide.link}">${slide.btnText}</button>
          </div>`;
      }

      fragment.appendChild(slideDiv);
    });
    return fragment;
  }

  function initCountdown() {
    const timerDiv = document.getElementById("countdownTimer");
    if (!timerDiv) return;

    let targetTime = Date.now() + 2 * 60 * 60 * 1000;

    function update() {
      const diff = Math.max(0, targetTime - Date.now());
      if (diff <= 0) {
        timerDiv.innerHTML = '<span class="timer-number">00:00:00</span>';
        return;
      }

      const hours = Math.floor(diff / 3600000);
      const mins = Math.floor((diff % 3600000) / 60000);
      const secs = Math.floor((diff % 60000) / 1000);

      timerDiv.innerHTML = `
        <div class="timer-unit"><span class="timer-number">${String(hours).padStart(2, "0")}</span><span>Horas</span></div>
        <div class="timer-unit"><span class="timer-number">${String(mins).padStart(2, "0")}</span><span>Min</span></div>
        <div class="timer-unit"><span class="timer-number">${String(secs).padStart(2, "0")}</span><span>Seg</span></div>`;
    }

    update();
    setInterval(update, 1000);
  }

  function goToSlide(index) {
    if (!wrapper) return;
    if (index < 0) index = 0;
    if (index >= totalSlides) index = totalSlides - 1;
    currentIndex = index;
    wrapper.style.transform = `translateX(-${currentIndex * 100}%)`;
    document.querySelectorAll(".dot").forEach((dot, i) => dot.classList.toggle("active", i === currentIndex));
    if (!isPaused) resetProgressBar();
    if (autoInterval && !isPaused) {
      clearInterval(autoInterval);
      autoInterval = setInterval(autoSlide, slideDuration);
    }
  }

  function resetProgressBar() {
    if (!progressBar) return;
    progressBar.style.transition = "none";
    progressBar.style.width = "0%";
    void progressBar.offsetHeight;
    progressBar.style.transition = `width ${slideDuration}ms linear`;
    setTimeout(() => { if (progressBar && !isPaused) progressBar.style.width = "100%"; }, 20);
  }

  function autoSlide() {
    if (!isPaused) goToSlide((currentIndex + 1) % totalSlides);
  }

  function pauseCarousel() {
    if (isPaused) return;
    isPaused = true;
    if (autoInterval) clearInterval(autoInterval);
    if (progressBar) progressBar.style.transition = "none";
    if (carouselSection) carouselSection.classList.add("paused");
  }

  function resumeCarousel() {
    if (!isPaused) return;
    isPaused = false;
    autoInterval = setInterval(autoSlide, slideDuration);
    resetProgressBar();
    if (carouselSection) carouselSection.classList.remove("paused");
  }

  function bindSlideActions() {
    document.querySelectorAll(".slide-action").forEach(btn => btn.addEventListener("click", (e) => {
      e.preventDefault();
      const link = btn.getAttribute("data-link");
      if (link) window.location.href = baseUrl + link;
    }));
  }

  function initHeroCarousel() {
    const container = document.getElementById("heroCarouselContainer");
    if (!container) return;

    carouselSection = document.getElementById("heroCarouselSection");
    const viewport = document.createElement("div");
    viewport.className = "slides-viewport";

    wrapper = document.createElement("div");
    wrapper.className = "slide-wrapper";
    wrapper.appendChild(buildSlides());
    viewport.appendChild(wrapper);

    progressBar = document.createElement("div");
    progressBar.className = "carousel-progress";
    viewport.appendChild(progressBar);

    const btnLeft = document.createElement("button");
    btnLeft.className = "nav-btn nav-btn-left";
    btnLeft.type = "button";
    btnLeft.setAttribute("aria-label", "Diapositiva anterior");
    btnLeft.innerHTML = "‹";

    const btnRight = document.createElement("button");
    btnRight.className = "nav-btn nav-btn-right";
    btnRight.type = "button";
    btnRight.setAttribute("aria-label", "Siguiente diapositiva");
    btnRight.innerHTML = "›";

    viewport.appendChild(btnLeft);
    viewport.appendChild(btnRight);

    const dotsDiv = document.createElement("div");
    dotsDiv.className = "dots-container";
    dotsDiv.setAttribute("aria-label", "Navegación del carrusel");
    for (let i = 0; i < totalSlides; i++) {
      const dot = document.createElement("button");
      dot.type = "button";
      dot.className = `dot ${i === 0 ? "active" : ""}`;
      dot.setAttribute("aria-label", `Ir a la diapositiva ${i + 1}`);
      dot.addEventListener("click", () => {
        pauseCarousel();
        goToSlide(i);
        resumeCarousel();
      });
      dotsDiv.appendChild(dot);
    }
    viewport.appendChild(dotsDiv);

    container.appendChild(viewport);
    bindSlideActions();

    btnLeft.addEventListener("click", () => {
      pauseCarousel();
      goToSlide(currentIndex - 1);
      resumeCarousel();
    });

    btnRight.addEventListener("click", () => {
      pauseCarousel();
      goToSlide(currentIndex + 1);
      resumeCarousel();
    });

    carouselSection?.addEventListener("mouseenter", pauseCarousel);
    carouselSection?.addEventListener("mouseleave", resumeCarousel);

    let touchStartX = 0;
    viewport.addEventListener("touchstart", (e) => {
      touchStartX = e.changedTouches[0].clientX;
      pauseCarousel();
    }, { passive: true });

    viewport.addEventListener("touchend", (e) => {
      const diff = e.changedTouches[0].clientX - touchStartX;
      if (Math.abs(diff) > 40) goToSlide(currentIndex + (diff > 0 ? -1 : 1));
      resumeCarousel();
    }, { passive: true });

    autoInterval = setInterval(autoSlide, slideDuration);
    resetProgressBar();

    setTimeout(() => {
      initCountdown();
    }, 100);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeroCarousel);
  } else {
    initHeroCarousel();
  }
})();

// ======================== OFERTAS RELÁMPAGO PROFESIONALES ========================
(function() {
  const baseUrl = (typeof window.APP_URL === "string") ? window.APP_URL : "";
  const offersData = [
    { id: 1, productId: 1, name: "Conjunto deportivo", oldPrice: 129900, newPrice: 89900, discount: "31%", icon: "Moda", endTimeOffset: 2.5 * 3600 * 1000 },
    { id: 2, productId: 6, name: "Conjunto infantil", oldPrice: 119900, newPrice: 85900, discount: "28%", icon: "Niñas", endTimeOffset: 5 * 3600 * 1000 },
    { id: 3, productId: 5, name: "Set bebé premium", oldPrice: 139900, newPrice: 99900, discount: "29%", icon: "Bebés", endTimeOffset: 1.2 * 3600 * 1000 }
  ];

  function notify(title, message, type = "info") {
    if (typeof window.showToast === "function") {
      window.showToast({ title, message, type });
    }
  }

  const grid = document.getElementById("dynamicOffersGrid");
  if (!grid) return;

  function formatTime(ms) {
    if (ms <= 0) return "00:00:00";
    const hours = Math.floor(ms / 3600000);
    const mins = Math.floor((ms % 3600000) / 60000);
    const secs = Math.floor((ms % 60000) / 1000);
    return `${String(hours).padStart(2, "0")}:${String(mins).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
  }

  function updateTimers() {
    const now = Date.now();
    offersData.forEach(offer => {
      const remaining = Math.max(0, offer.endTime - now);
      const timerEl = document.getElementById(`timer-${offer.id}`);
      if (timerEl) timerEl.textContent = formatTime(remaining);
      const card = document.getElementById(`offer-card-${offer.id}`);
      const button = card ? card.querySelector(".btn-offer") : null;
      if (card) card.classList.toggle("expired", remaining <= 0);
      if (button) button.disabled = remaining <= 0;
    });
  }

  function openProduct(id) {
    if (typeof window.openProductDetail === "function") {
      window.openProductDetail(id);
    } else if (typeof window.openDetail === "function") {
      window.openDetail(id);
    } else {
      window.location.href = baseUrl + "/compra";
    }
  }

  function initOffers() {
    const nowBase = Date.now();
    offersData.forEach(offer => { offer.endTime = nowBase + offer.endTimeOffset; });

    grid.innerHTML = "";
    offersData.forEach(offer => {
      const card = document.createElement("div");
      card.className = "offer-card";
      card.id = `offer-card-${offer.id}`;
      card.innerHTML = `
        <div class="offer-tag">-${offer.discount}</div>
        <div class="offer-img">
          <span class="offer-icon">${offer.icon}</span>
        </div>
        <div class="offer-title">${offer.name}</div>
        <div class="offer-price">
          <span class="old-price">$${offer.oldPrice.toLocaleString()}</span>
          <span class="new-price">$${offer.newPrice.toLocaleString()}</span>
        </div>
        <div class="offer-countdown">
          <span>Termina en</span>
          <strong id="timer-${offer.id}">--:--:--</strong>
        </div>
        <button class="btn-offer" type="button">Ver oferta</button>
      `;
      const button = card.querySelector(".btn-offer");
      button.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        openProduct(offer.productId);
        notify("Oferta seleccionada", `${offer.name} agregado a tu vista rápida`, "success");
      });

      card.addEventListener("click", () => openProduct(offer.productId));
      grid.appendChild(card);
    });

    updateTimers();
    setInterval(updateTimers, 1000);
  }

  initOffers();
})();

// ======================== SCRATCH CARD SIMPLE ========================
(function() {
  const surface = document.getElementById("scratchSurface");
  const coupon = document.getElementById("couponResult");
  const text = document.getElementById("scratchText");
  if (!surface || !coupon) return;

  const coupons = ["10% OFF", "15% OFF", "Envío gratis", "Bono $20.000"];
  const selected = coupons[Math.floor(Math.random() * coupons.length)];
  let revealed = false;

  function notify(title, message, type = "info") {
    if (typeof window.showToast === "function") {
      window.showToast({ title, message, type });
    }
  }

  surface.addEventListener("click", () => {
    if (revealed) return;
    revealed = true;
    surface.classList.add("revealed");
    if (text) text.textContent = "¡Descuento revelado!";
    coupon.textContent = `Tu cupón: ${selected}`;
    notify("Cupón desbloqueado", coupon.textContent, "success");
  });
})();

