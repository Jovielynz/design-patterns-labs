<?php
// Lab 3 - WITH Composite (GOOD)

// ---------- Forum Composite ----------
interface ForumComponent
{
    public function display(int $depth = 0): void;
}

class Post implements ForumComponent
{
    public function __construct(
        private string $author,
        private string $message
    ) {}

    public function display(int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);

        echo $indent . "- Post by {$this->author}: {$this->message}\n";
    }
}

// ---------- New Leaf for Extensibility ----------
class Bundle implements ForumComponent
{
    public function __construct(
        private string $name,
        private string $description
    ) {}

    public function display(int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);

        echo $indent . "* Bundle: {$this->name} - {$this->description}\n";
    }
}

class Thread implements ForumComponent
{
    /** @var ForumComponent[] */
    private array $children = [];

    public function __construct(private string $title) {}

    public function add(ForumComponent $c): void
    {
        $this->children[] = $c;
    }

    public function display(int $depth = 0): void
    {
        $indent = str_repeat("  ", $depth);

        echo $indent . "+ Thread: {$this->title}\n";

        foreach ($this->children as $child) {
            $child->display($depth + 1);
        }
    }

    public static function fromApi(int $postId): self
    {
        $postJson = file_get_contents(
            "https://jsonplaceholder.typicode.com/posts/$postId"
        );

        $post = json_decode($postJson, true);

        $thread = new self($post["title"]);

        $thread->add(
            new Post(
                "Author {$post['userId']}",
                substr($post["body"], 0, 40) . "..."
            )
        );

        $commentsJson = file_get_contents(
            "https://jsonplaceholder.typicode.com/posts/$postId/comments"
        );

        $comments = json_decode($commentsJson, true);

        $replies = new Thread("Replies");

        foreach (array_slice($comments, 0, 2) as $c) {
            $replies->add(
                new Post(
                    $c["email"],
                    substr($c["body"], 0, 30) . "..."
                )
            );
        }

        $thread->add($replies);

        return $thread;
    }
}

// ---------- RAG Composite ----------
interface TextComponent
{
    public function getText(): string;

    public function embed(): void;
}

class Chunk implements TextComponent
{
    public function __construct(private string $text) {}

    public function getText(): string
    {
        return $this->text;
    }

    public function embed(): void
    {
        echo "Embedding chunk: {$this->text}\n";
    }
}

class Section implements TextComponent
{
    /** @var TextComponent[] */
    private array $children = [];

    public function __construct(private string $title) {}

    public function add(TextComponent $component): void
    {
        $this->children[] = $component;
    }

    public function getText(): string
    {
        $text = "Section: {$this->title}\n";

        foreach ($this->children as $child) {
            $text .= $child->getText() . "\n";
        }

        return $text;
    }

    public function embed(): void
    {
        foreach ($this->children as $child) {
            $child->embed();
        }
    }
}

class Document implements TextComponent
{
    /** @var TextComponent[] */
    private array $children = [];

    public function __construct(private string $title) {}

    public function add(TextComponent $component): void
    {
        $this->children[] = $component;
    }

    public function getText(): string
    {
        $text = "Document: {$this->title}\n";

        foreach ($this->children as $child) {
            $text .= $child->getText() . "\n";
        }

        return $text;
    }

    public function embed(): void
    {
        foreach ($this->children as $child) {
            $child->embed();
        }
    }
}

// ---------- Demo ----------
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {

    echo "WITH Composite (GOOD):\n\n";

    // Forum demo
    echo "FORUM COMPOSITE:\n";

    $thread = Thread::fromApi(1);

    $thread->add(
        new Bundle(
            "Starter Pack",
            "Pre-set forum content bundle"
        )
    );

    $thread->display();

    echo "\nRAG COMPOSITE:\n";

    // RAG demo
    $document = new Document("IPT Notes");

    $section = new Section("Design Patterns");

    $section->add(
        new Chunk("Composite represents part-whole hierarchies.")
    );

    $section->add(
        new Chunk("Leaf and composite objects share one interface.")
    );

    $document->add($section);

    echo $document->getText();

    echo "\nEMBEDDING:\n";

    $document->embed();
}