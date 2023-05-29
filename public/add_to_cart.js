//добавление товара в корзину
function addToCart(button) {
    const form = button.parentElement;
    const formData = new FormData(form);//получение формы, где была нажата кнопка
   //отправка запроса 
    fetch('/add-cart', {

      method: 'POST',
      body: formData
    })
    //проверка ответа сервера
    .then(response => {
        updateCartCount();
    })
    //отлов ошибки
    .catch(error => {
      console.error('Error adding item to cart', error);
      alert('Error adding item to cart');
    });
  }
//обновление отображения количества
  function updateCartCount() {
    //отправка запрова
    fetch('/cart-data')
      .then(response => response.json())
      .then(data => {
        // d = JSON.parse(data);//разборка
        const count = data['count'];
        const cartCountEl = document.querySelector('.cart__count');//получает ссылку на HTML-элемент, который отображает количество товаров в корзине
        cartCountEl.textContent = count;//обновление текстового содержимого
      })
      //отлов ошибки
      .catch(error => console.error('Error fetching cart count:', error));
  }

  updateCartCount()