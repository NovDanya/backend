<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Форма</title>
</head>
<body>
    <h1>Заполните форму</h1>
    <form action="form.php" method="POST">
        <label for="fio">ФИО:</label>
        <input type="text" id="fio" name="fio" required>

        <label for="phone">Телефон:</label>
        <input type="tel" id="phone" name="phone" required>

        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email" required>

        <label for="birthdate">Дата рождения:</label>
        <input type="date" id="birthdate" name="birthdate" required>

        <label>Пол:</label>
        <div class="gender-group">
            <div class="gender-option">
                <input type="radio" id="male" name="gender" value="male" required>
                <label for="male">Мужской</label>
            </div>
            <div class="gender-option">
                <input type="radio" id="female" name="gender" value="female">
                <label for="female">Женский</label>
            </div>
        </div>

        <label for="languages">Любимый язык программирования:</label>
        <select id="languages" name="languages[]" multiple required>
            <option value="1">Pascal</option>
            <option value="2">C</option>
            <option value="3">C++</option>
            <option value="4">JavaScript</option>
            <option value="5">PHP</option>
            <option value="6">Python</option>
            <option value="7">Java</option>
            <option value="8">Haskell</option>
            <option value="9">Clojure</option>
            <option value="10">Prolog</option>
            <option value="11">Scala</option>
            <option value="12">Go</option>
        </select>

        <label for="bio">Биография:</label>
        <textarea id="bio" name="bio" rows="5" required></textarea>

        <div class="contract-group">
            <input type="checkbox" id="contract" name="contract" value="yes" required>
            <label for="contract">С контрактом ознакомлен(а)</label>
        </div>

        <input type="submit" value="Сохранить">
    </form>
</body>
</html>