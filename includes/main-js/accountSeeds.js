// Demo account seeds for login simulation (PasswordHash is plain text for demo)

const accountSeeds = [
  {
    Id: 1,
    Username: "admin",
    Email: "admin@gmail.com",
    PasswordHash: "admin123",
    Role: "Admin",
    FirstName: "System",
    LastName: "Administrator",
    ProfileImage: null,
    DateOfBirth: "1980-01-01",
    LastLoginAt: null,
    CreatedAt: "2024-01-01 09:00:00",
    UpdatedAt: "2024-01-01 09:00:00"
  },
  {
    Id: 2,
    Username: "agent01",
    Email: "agent01@gmail.com",
    PasswordHash: "agentpass",
    Role: "SalesAgent",
    FirstName: "John",
    LastName: "Smith",
    ProfileImage: null,
    DateOfBirth: "1992-08-15",
    LastLoginAt: null,
    CreatedAt: "2024-01-03 11:00:00",
    UpdatedAt: "2024-01-03 11:00:00"
  },
  {
    Id: 3,
    Username: "customer01",
    Email: "customer01@email.com",
    PasswordHash: "customerpass",
    Role: "Customer",
    FirstName: "Alice",
    LastName: "Lee",
    ProfileImage: null,
    DateOfBirth: "1995-12-20",
    LastLoginAt: null,
    CreatedAt: "2024-01-04 12:00:00",
    UpdatedAt: "2024-01-04 12:00:00"
  }
];

function addAccountSeed(newAccount) {
  if (
    accountSeeds.some(
      acc =>
        acc.Email.toLowerCase() === newAccount.Email.toLowerCase() ||
        acc.Username.toLowerCase() === newAccount.Username.toLowerCase()
    )
  ) {
    return false; // Account already exists
  }
  newAccount.Id = accountSeeds.length ? Math.max(...accountSeeds.map(a => a.Id)) + 1 : 1;
  accountSeeds.push(newAccount);
  return true;
}

// For browser global use; remove export if not using modules
// export default accountSeeds;
