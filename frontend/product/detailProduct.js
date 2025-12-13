const API = "/TUBES_2_Toko/backend/product/detail.php";
const params = new URLSearchParams(window.location.search);
const productId = params.get("id");

async function loadDetail() {
  const res = await fetch(`${API}?id=${productId}`, { credentials: "include" });
  const json = await res.json();

  if (!json.ok) {
    document.getElementById("detail-root").innerHTML =
      "<p>Product not found.</p>";
    return;
  }

  const p = json.data;

  document.getElementById("detail-root").innerHTML = `
    <div class="image-section">
      <img src="${p.image}" alt="${p.title}">
    </div>

    <div class="info-section">
      <h1 class="product-title">${p.title}</h1>
      <div class="product-price">${p.price_text}</div>
      <p class="product-description">${p.description}</p>

      <div class="size-section">
        <span class="size-label">Size:</span>
        <div class="size-option">
          ${p.sizes.map(s =>
            `<button class="size-btn" data-size="${s}">${s}</button>`
          ).join("")}
        </div>
      </div>

      <form id="cartForm">
        <input type="hidden" id="qtyInput" value="1">

        <div class="qty-wrapper">
          <span class="qty-label">Quantity:</span>
          <div class="qty-control">
            <button type="button" id="minusBtn">-</button>
            <input type="text" id="qtyDisplay" value="1">
            <button type="button" id="plusBtn">+</button>
          </div>
        </div>

        <button class="add-cart-btn">Add to Cart</button>
      </form>

      <a href="listProduct.html" class="back-link">← Back to Products</a>
    </div>
  `;

  initInteractions(p.isLoggedIn);
}

function initInteractions(isLoggedIn) {
  let selectedSize = "";

  document.querySelectorAll(".size-btn").forEach(btn => {
    btn.onclick = () => {
      document.querySelectorAll(".size-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      selectedSize = btn.dataset.size;
    };
  });

  const qtyDisplay = document.getElementById("qtyDisplay");
  const qtyInput   = document.getElementById("qtyInput");

  document.getElementById("minusBtn").onclick = () => {
    qtyDisplay.value = Math.max(1, parseInt(qtyDisplay.value) - 1);
    qtyInput.value = qtyDisplay.value;
  };

  document.getElementById("plusBtn").onclick = () => {
    qtyDisplay.value = parseInt(qtyDisplay.value) + 1;
    qtyInput.value = qtyDisplay.value;
  };

  document.getElementById("cartForm").onsubmit = e => {
    e.preventDefault();
    if (!selectedSize) {
      alert("Please select a size.");
      return;
    }
    if (!isLoggedIn) {
      alert("Silakan login terlebih dahulu.");
      return;
    }

    fetch("/TUBES_2_Toko/backend/cart/add.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `product_id=${productId}&size=${selectedSize}&qty=${qtyInput.value}`
    }).then(() => {
      window.location.href = "../cart/listCart.html";
    });
  };
}

loadDetail();
