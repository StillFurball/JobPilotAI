import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    id: string;
    value: string[];
    onChange: (value: string[]) => void;
    placeholder?: string;
    lowercase?: boolean;
};

/**
 * Small chip editor for lists of strings (skills, keywords, locations).
 */
export default function TagInput({
    id,
    value,
    onChange,
    placeholder,
    lowercase = false,
}: Props) {
    const [draft, setDraft] = useState('');

    const add = () => {
        const tag = lowercase ? draft.trim().toLowerCase() : draft.trim();

        if (tag && !value.some((v) => v.toLowerCase() === tag.toLowerCase())) {
            onChange([...value, tag]);
        }

        setDraft('');
    };

    return (
        <div className="space-y-2">
            <div className="flex gap-2">
                <Input
                    id={id}
                    value={draft}
                    placeholder={placeholder}
                    onChange={(e) => setDraft(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ',') {
                            e.preventDefault();
                            add();
                        }
                    }}
                />
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={add}
                    aria-label="Add"
                >
                    <Plus />
                </Button>
            </div>
            {value.length > 0 && (
                <div className="flex flex-wrap gap-1.5">
                    {value.map((tag) => (
                        <Badge
                            key={tag}
                            variant="secondary"
                            className="gap-1 pr-1"
                        >
                            {tag}
                            <button
                                type="button"
                                className="rounded-sm p-0.5 hover:bg-muted-foreground/20"
                                onClick={() =>
                                    onChange(value.filter((t) => t !== tag))
                                }
                                aria-label={`Remove ${tag}`}
                            >
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))}
                </div>
            )}
        </div>
    );
}
