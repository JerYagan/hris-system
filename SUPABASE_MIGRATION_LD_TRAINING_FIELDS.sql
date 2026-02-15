begin;

alter table public.training_programs
  add column if not exists training_type text,
  add column if not exists training_category text,
  add column if not exists venue text,
  add column if not exists schedule_time time;

alter table public.training_programs
  alter column training_type set default 'General',
  alter column training_category set default 'General',
  alter column venue set default 'TBD';

update public.training_programs
set
  training_type = coalesce(
    nullif(training_type, ''),
    nullif(trim(split_part(title, ' - ', 1)), ''),
    'General'
  ),
  training_category = coalesce(
    nullif(training_category, ''),
    nullif(trim(split_part(title, ' - ', 2)), ''),
    'General'
  ),
  venue = coalesce(nullif(venue, ''), 'TBD')
where
  training_type is null
  or training_type = ''
  or training_category is null
  or training_category = ''
  or venue is null
  or venue = '';

alter table public.training_programs
  alter column training_type set not null,
  alter column training_category set not null,
  alter column venue set not null;

create index if not exists idx_training_programs_training_type on public.training_programs(training_type);
create index if not exists idx_training_programs_training_category on public.training_programs(training_category);

commit;
