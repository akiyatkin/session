{root:}
	<h1>Session REST-Сервис</h1>

	<ul> 
		<li><a href="/{root}/users">users</a> &mdash; список всех аккаунтов с паролями</li>
		<li><a href="/{root}/clear/email">clear/email</a> &mdash; удалить аккаунт с указанным email</li>
		<li><a href="/{root}/clear">clear</a> &mdash; удалить данне всех гостей</li>
		<li><a href="/{root}/stat">stat</a> &mdash; сколько база данных занимает места</li>
	</ul>
{users:}
	<h1>Все зарегистрированные пользователи</h1>
	<table class="table table-striped">
		<thead><tr>{head::head}</tr></thead>
		<tbody>{table::row}</tbody>
	</table>
	{head:}<th>{.}</th>
	{row:}<tr>{::cell}</tr>
		{cell:}<td>{.}</td>