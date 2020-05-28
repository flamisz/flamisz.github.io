---
layout: default
---
## How <strike>to</strike> I solved

<ul>
    {% for post in site.categories.how-i %}
    <li>
        <a href="{{ post.url }}">{{ post.title }}</a>
    </li>
    {% endfor %}
</ul>
